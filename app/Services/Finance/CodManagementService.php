<?php

namespace App\Services\Finance;

use App\Models\CodCollection;
use App\Models\Shipment;
use App\Models\DriverCashAccount;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * CodManagementService
 * 
 * Comprehensive COD (Cash on Delivery) management:
 * - Collection tracking
 * - Verification workflow
 * - Remittance management
 * - Driver cash accounts
 * - Discrepancy handling
 */
class CodManagementService
{
    /**
     * Create COD collection record when shipment is created
     */
    public function createCollection(Shipment $shipment): ?CodCollection
    {
        if ($shipment->payment_type !== 'cod' || !$shipment->cod_amount) {
            return null;
        }

        return CodCollection::create([
            'shipment_id' => $shipment->id,
            'expected_amount' => $shipment->cod_amount,
            'currency' => $shipment->currency ?? 'USD',
            'branch_id' => $shipment->dest_branch_id,
            'status' => 'pending',
        ]);
    }

    /**
     * Record COD collection by driver/agent
     */
    public function recordCollection(
        CodCollection $collection,
        float $amount,
        int $collectorId,
        string $method = 'cash',
        ?string $reference = null
    ): CodCollection {
        return DB::transaction(function () use ($collection, $amount, $collectorId, $method, $reference) {
            $collection->update([
                'collected_amount' => $amount,
                'collected_by' => $collectorId,
                'collection_method' => $method,
                'payment_reference' => $reference,
                'status' => 'collected',
                'collected_at' => now(),
            ]);

            // Update driver cash account
            $account = DriverCashAccount::getOrCreate($collectorId);
            $account->addCollection($amount);

            // Record transaction
            FinancialTransaction::recordCodCollection(
                $collection->shipment,
                $amount,
                $collectorId,
                $collection->branch_id,
                $method,
                $reference
            );

            return $collection->fresh();
        });
    }

    /**
     * Verify collected amount
     */
    public function verifyCollection(CodCollection $collection, int $verifierId): CodCollection
    {
        $collection->update([
            'status' => 'verified',
            'verified_at' => now(),
            'metadata' => array_merge($collection->metadata ?? [], [
                'verified_by' => $verifierId,
            ]),
        ]);

        return $collection->fresh();
    }

    /**
     * Record remittance from driver
     */
    public function recordRemittance(
        array $collectionIds,
        int $driverId,
        float $totalAmount,
        ?string $reference = null
    ): array {
        return DB::transaction(function () use ($collectionIds, $driverId, $totalAmount, $reference) {
            $collections = CodCollection::whereIn('id', $collectionIds)
                ->where('collected_by', $driverId)
                ->whereIn('status', ['collected', 'verified'])
                ->get();

            $remittedAmount = 0;
            $remittedIds = [];

            foreach ($collections as $collection) {
                $collection->update([
                    'status' => 'remitted',
                    'remitted_at' => now(),
                    'metadata' => array_merge($collection->metadata ?? [], [
                        'remittance_reference' => $reference,
                        'remittance_batch_amount' => $totalAmount,
                    ]),
                ]);

                $remittedAmount += $collection->collected_amount;
                $remittedIds[] = $collection->id;
            }

            // Update driver account
            $account = DriverCashAccount::getOrCreate($driverId);
            $account->recordRemittance($remittedAmount);

            return [
                'remitted_count' => count($remittedIds),
                'remitted_amount' => $remittedAmount,
                'remitted_ids' => $remittedIds,
                'driver_balance' => $account->balance,
            ];
        });
    }

    /**
     * Get pending collections for driver
     */
    public function getDriverPendingCollections(int $driverId): Collection
    {
        return CodCollection::with('shipment')
            ->where('collected_by', $driverId)
            ->whereIn('status', ['collected', 'verified'])
            ->whereNull('remitted_at')
            ->orderBy('collected_at')
            ->get();
    }

    /**
     * Get collections needing verification
     */
    public function getCollectionsNeedingVerification(?int $branchId = null): Collection
    {
        return CodCollection::with(['shipment', 'collector'])
            ->where('status', 'collected')
            ->whereNull('verified_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('collected_at')
            ->get();
    }

    /**
     * Get collections with discrepancies
     */
    public function getDiscrepancies(?int $branchId = null): Collection
    {
        return CodCollection::with(['shipment', 'collector'])
            ->whereNotNull('collected_amount')
            ->whereRaw('ABS(expected_amount - collected_amount) > 0.01')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($collection) {
                $collection->discrepancy_amount = $collection->discrepancy;
                return $collection;
            });
    }

    /**
     * Get COD summary statistics
     */
    public function getCodSummary(array $filters = []): array
    {
        $query = CodCollection::query();

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $collected = (clone $query)->whereIn('status', ['collected', 'verified'])->count();
        $remitted = (clone $query)->where('status', 'remitted')->count();

        $totalExpected = (clone $query)->sum('expected_amount');
        $totalCollected = (clone $query)->whereNotNull('collected_amount')->sum('collected_amount');
        $totalRemitted = (clone $query)->where('status', 'remitted')->sum('collected_amount');

        $pendingRemittance = (clone $query)
            ->whereIn('status', ['collected', 'verified'])
            ->whereNull('remitted_at')
            ->sum('collected_amount');

        return [
            'counts' => [
                'total' => $total,
                'pending' => $pending,
                'collected' => $collected,
                'remitted' => $remitted,
            ],
            'amounts' => [
                'total_expected' => round($totalExpected, 2),
                'total_collected' => round($totalCollected, 2),
                'total_remitted' => round($totalRemitted, 2),
                'pending_remittance' => round($pendingRemittance, 2),
            ],
            'collection_rate' => $total > 0 
                ? round((($collected + $remitted) / $total) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Get driver COD performance
     */
    public function getDriverCodPerformance(int $driverId, array $dateRange = []): array
    {
        $query = CodCollection::where('collected_by', $driverId);

        if (!empty($dateRange['start'])) {
            $query->where('collected_at', '>=', $dateRange['start']);
        }
        if (!empty($dateRange['end'])) {
            $query->where('collected_at', '<=', $dateRange['end']);
        }

        $collections = $query->get();
        $account = DriverCashAccount::where('driver_id', $driverId)->first();

        $discrepancies = $collections->filter(fn($c) => $c->hasDiscrepancy());

        return [
            'total_collections' => $collections->count(),
            'total_collected' => round($collections->sum('collected_amount'), 2),
            'total_remitted' => round($collections->where('status', 'remitted')->sum('collected_amount'), 2),
            'pending_remittance' => round($account?->pending_remittance ?? 0, 2),
            'current_balance' => round($account?->balance ?? 0, 2),
            'discrepancy_count' => $discrepancies->count(),
            'total_discrepancy' => round($discrepancies->sum('discrepancy'), 2),
            'last_remittance' => $account?->last_remittance_at?->toIso8601String(),
        ];
    }
}
