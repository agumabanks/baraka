<?php

namespace App\Services;

use App\Models\Backend\Branch;
use App\Models\Consolidation;
use App\Models\ConsolidationRule;
use App\Models\DeconsolidationEvent;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ConsolidationService
 * 
 * Handles all consolidation business logic including:
 * - Creating and managing consolidations
 * - Auto-consolidation based on rules
 * - Deconsolidation workflows
 * - Consolidation optimization
 */
class ConsolidationService
{
    /**
     * Create a new consolidation
     */
    public static function createConsolidation(
        Branch $branch,
        string $type,
        string $destination,
        User $createdBy,
        array $options = []
    ): Consolidation {
        $consolidationNumber = static::generateConsolidationNumber($branch, $type);

        $consolidation = Consolidation::create([
            'branch_id' => $branch->id,
            'consolidation_number' => $consolidationNumber,
            'type' => $type,
            'destination' => $destination,
            'destination_branch_id' => $options['destination_branch_id'] ?? null,
            'status' => 'OPEN',
            'max_pieces' => $options['max_pieces'] ?? null,
            'max_weight_kg' => $options['max_weight_kg'] ?? null,
            'max_volume_cbm' => $options['max_volume_cbm'] ?? null,
            'cutoff_time' => $options['cutoff_time'] ?? null,
            'transport_mode' => $options['transport_mode'] ?? null,
            'created_by' => $createdBy->id,
        ]);

        activity()
            ->performedOn($consolidation)
            ->causedBy($createdBy)
            ->log("Consolidation {$consolidationNumber} created");

        return $consolidation;
    }

    /**
     * Generate unique consolidation number
     */
    protected static function generateConsolidationNumber(Branch $branch, string $type): string
    {
        $prefix = $type === 'BBX' ? 'BBX' : 'LBX';
        $branchCode = $branch->code ?? 'HQ';
        $date = now()->format('Ymd');
        
        // Get today's count for this branch and type
        $count = Consolidation::where('branch_id', $branch->id)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%s-%04d', $prefix, $branchCode, $date, $count);
    }

    /**
     * Find or create consolidation for shipment based on rules
     */
    public static function findOrCreateConsolidationForShipment(
        Shipment $shipment,
        User $user
    ): ?Consolidation {
        // Get applicable rules for the shipment's branch
        $rules = ConsolidationRule::active()
            ->forBranch($shipment->origin_branch_id ?? $shipment->branch_id)
            ->byPriority()
            ->get();

        foreach ($rules as $rule) {
            if (!$rule->matchesShipment($shipment)) {
                continue;
            }

            // Try to find existing open consolidation matching this rule
            $consolidation = static::findOpenConsolidation($shipment, $rule);

            if ($consolidation && $consolidation->canAcceptShipment($shipment)) {
                return $consolidation;
            }

            // Create new consolidation if none found
            return static::createConsolidationFromRule($shipment, $rule, $user);
        }

        return null;
    }

    /**
     * Find existing open consolidation for shipment
     */
    protected static function findOpenConsolidation(Shipment $shipment, ConsolidationRule $rule): ?Consolidation
    {
        $destination = static::determineDestination($shipment, $rule);

        return Consolidation::open()
            ->where('branch_id', $shipment->origin_branch_id ?? $shipment->branch_id)
            ->where('type', $rule->consolidation_type)
            ->where('destination', $destination)
            ->where(function ($query) {
                $query->whereNull('cutoff_time')
                    ->orWhere('cutoff_time', '>', now());
            })
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Create consolidation from rule
     */
    protected static function createConsolidationFromRule(
        Shipment $shipment,
        ConsolidationRule $rule,
        User $user
    ): Consolidation {
        $destination = static::determineDestination($shipment, $rule);
        $cutoffTime = $rule->getCutoffTimeForDate(now());

        return static::createConsolidation(
            $shipment->originBranch ?? $shipment->branch,
            $rule->consolidation_type,
            $destination,
            $user,
            [
                'destination_branch_id' => $rule->destination_branch_id,
                'max_pieces' => $rule->max_pieces,
                'max_weight_kg' => $rule->max_weight_kg,
                'cutoff_time' => $cutoffTime,
            ]
        );
    }

    /**
     * Determine destination string for consolidation
     */
    protected static function determineDestination(Shipment $shipment, ConsolidationRule $rule): string
    {
        if ($rule->destination_city && $rule->destination_country) {
            return "{$rule->destination_city}, {$rule->destination_country}";
        }

        if ($rule->destination_city) {
            return $rule->destination_city;
        }

        if ($rule->destination_country) {
            return $rule->destination_country;
        }

        // Fallback to shipment destination
        return $shipment->destination_city ?? $shipment->destination_country ?? 'Unknown';
    }

    /**
     * Auto-consolidate eligible shipments
     */
    public static function autoConsolidateShipments(Branch $branch, User $user): array
    {
        $results = [
            'consolidated' => 0,
            'created_consolidations' => 0,
            'errors' => [],
        ];

        // Get shipments eligible for consolidation
        $shipments = Shipment::where('origin_branch_id', $branch->id)
            ->whereNull('consolidation_id')
            ->whereIn('status', ['READY_FOR_CONSOLIDATION', 'WAREHOUSE', 'PENDING'])
            ->get();

        foreach ($shipments as $shipment) {
            try {
                $consolidation = static::findOrCreateConsolidationForShipment($shipment, $user);

                if ($consolidation) {
                    $added = $consolidation->addShipment($shipment, $user);
                    
                    if ($added) {
                        $results['consolidated']++;
                        
                        // Track if this was a new consolidation
                        if ($consolidation->wasRecentlyCreated) {
                            $results['created_consolidations']++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Auto-consolidation error', [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results['errors'][] = [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Begin deconsolidation and scan baby shipments
     */
    public static function scanBabyShipment(
        Consolidation $consolidation,
        Shipment $shipment,
        User $user,
        Branch $branch
    ): bool {
        // Ensure consolidation is in deconsolidating status
        if ($consolidation->status === 'ARRIVED') {
            $consolidation->startDeconsolidation($user);
        }

        if (!in_array($consolidation->status, ['DECONSOLIDATING', 'ARRIVED'])) {
            return false;
        }

        // Verify shipment belongs to this consolidation
        if ($shipment->consolidation_id !== $consolidation->id) {
            return false;
        }

        // Record scan event
        DeconsolidationEvent::create([
            'consolidation_id' => $consolidation->id,
            'shipment_id' => $shipment->id,
            'branch_id' => $branch->id,
            'event_type' => 'SHIPMENT_SCANNED',
            'performed_by' => $user->id,
            'occurred_at' => now(),
        ]);

        // Update shipment status
        $shipment->update([
            'status' => 'DECONSOLIDATED',
        ]);

        activity()
            ->performedOn($consolidation)
            ->causedBy($user)
            ->withProperties(['shipment_id' => $shipment->id])
            ->log("Baby shipment {$shipment->tracking_number} scanned during deconsolidation");

        return true;
    }

    /**
     * Release baby shipment from consolidation
     */
    public static function releaseBabyShipment(
        Consolidation $consolidation,
        Shipment $shipment,
        User $user,
        Branch $branch
    ): bool {
        if ($consolidation->status !== 'DECONSOLIDATING') {
            return false;
        }

        // Record release event
        DeconsolidationEvent::create([
            'consolidation_id' => $consolidation->id,
            'shipment_id' => $shipment->id,
            'branch_id' => $branch->id,
            'event_type' => 'SHIPMENT_RELEASED',
            'performed_by' => $user->id,
            'occurred_at' => now(),
        ]);

        // Update shipment for further processing
        $shipment->update([
            'status' => 'READY_FOR_DELIVERY',
            'current_branch_id' => $branch->id,
        ]);

        activity()
            ->performedOn($consolidation)
            ->causedBy($user)
            ->withProperties(['shipment_id' => $shipment->id])
            ->log("Baby shipment {$shipment->tracking_number} released from consolidation");

        // Check if all shipments are deconsolidated
        $remaining = $consolidation->babyShipments()
            ->wherePivot('status', '!=', 'DECONSOLIDATED')
            ->count();

        if ($remaining === 0) {
            $consolidation->completeDeconsolidation($user);
        }

        return true;
    }

    /**
     * Report discrepancy during deconsolidation
     */
    public static function reportDiscrepancy(
        Consolidation $consolidation,
        Shipment $shipment,
        User $user,
        Branch $branch,
        string $discrepancyType,
        array $discrepancyData,
        ?string $notes = null
    ): void {
        DeconsolidationEvent::create([
            'consolidation_id' => $consolidation->id,
            'shipment_id' => $shipment->id,
            'branch_id' => $branch->id,
            'event_type' => 'DISCREPANCY',
            'notes' => $notes,
            'discrepancy_data' => array_merge($discrepancyData, ['type' => $discrepancyType]),
            'performed_by' => $user->id,
            'occurred_at' => now(),
        ]);

        activity()
            ->performedOn($consolidation)
            ->causedBy($user)
            ->withProperties([
                'shipment_id' => $shipment->id,
                'discrepancy_type' => $discrepancyType,
            ])
            ->log("Discrepancy reported for shipment {$shipment->tracking_number}");
    }

    /**
     * Get consolidation statistics for a branch
     */
    public static function getBranchStatistics(Branch $branch, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = Consolidation::where('branch_id', $branch->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $consolidations = $query->get();

        return [
            'total_consolidations' => $consolidations->count(),
            'open_consolidations' => $consolidations->where('status', 'OPEN')->count(),
            'in_transit_consolidations' => $consolidations->where('status', 'IN_TRANSIT')->count(),
            'completed_consolidations' => $consolidations->where('status', 'COMPLETED')->count(),
            'total_shipments_consolidated' => $consolidations->sum('current_pieces'),
            'total_weight_kg' => $consolidations->sum('current_weight_kg'),
            'avg_utilization' => $consolidations->avg('utilization_percentage'),
            'bbx_count' => $consolidations->where('type', 'BBX')->count(),
            'lbx_count' => $consolidations->where('type', 'LBX')->count(),
        ];
    }

    /**
     * Auto-lock consolidations that have reached cutoff time
     */
    public static function autoLockExpiredConsolidations(User $systemUser): int
    {
        $locked = 0;

        $expiredConsolidations = Consolidation::open()
            ->whereNotNull('cutoff_time')
            ->where('cutoff_time', '<=', now())
            ->where('current_pieces', '>', 0)
            ->get();

        foreach ($expiredConsolidations as $consolidation) {
            if ($consolidation->lock($systemUser)) {
                $locked++;
            }
        }

        return $locked;
    }
}
