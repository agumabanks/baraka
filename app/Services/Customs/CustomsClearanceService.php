<?php

namespace App\Services\Customs;

use App\Models\Shipment;
use App\Models\User;
use App\Enums\ShipmentStatus;
use App\Services\Logistics\ShipmentLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomsClearanceService
{
    protected ShipmentLifecycleService $lifecycleService;

    public function __construct(ShipmentLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Customs clearance statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_DOCUMENTS_REQUIRED = 'documents_required';
    public const STATUS_UNDER_INSPECTION = 'under_inspection';
    public const STATUS_DUTY_REQUIRED = 'duty_required';
    public const STATUS_CLEARED = 'cleared';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_HELD = 'held';

    /**
     * Place shipment on customs hold
     */
    public function placeOnCustomsHold(
        Shipment $shipment,
        string $reason,
        ?User $user = null,
        array $metadata = []
    ): Shipment {
        DB::transaction(function () use ($shipment, $reason, $user, $metadata) {
            $shipment->update([
                'customs_status' => self::STATUS_HELD,
                'customs_hold_reason' => $reason,
                'customs_hold_at' => now(),
                'customs_metadata' => array_merge(
                    $shipment->customs_metadata ?? [],
                    $metadata,
                    ['hold_reason' => $reason, 'hold_at' => now()->toISOString()]
                ),
            ]);

            // Transition shipment status
            $this->lifecycleService->transition(
                $shipment,
                ShipmentStatus::CUSTOMS_HOLD,
                [
                    'performed_by' => $user?->id,
                    'trigger' => 'customs_hold',
                    'customs_reason' => $reason,
                ]
            );
        });

        Log::info('Shipment placed on customs hold', [
            'shipment_id' => $shipment->id,
            'reason' => $reason,
            'user_id' => $user?->id,
        ]);

        return $shipment->fresh();
    }

    /**
     * Request additional documents
     */
    public function requestDocuments(
        Shipment $shipment,
        array $requiredDocuments,
        ?string $notes = null,
        ?User $user = null
    ): Shipment {
        $shipment->update([
            'customs_status' => self::STATUS_DOCUMENTS_REQUIRED,
            'customs_required_documents' => $requiredDocuments,
            'customs_document_notes' => $notes,
            'customs_documents_requested_at' => now(),
            'customs_metadata' => array_merge(
                $shipment->customs_metadata ?? [],
                [
                    'documents_requested' => $requiredDocuments,
                    'requested_at' => now()->toISOString(),
                    'requested_by' => $user?->id,
                ]
            ),
        ]);

        // TODO: Send notification to shipper/consignee

        Log::info('Customs documents requested', [
            'shipment_id' => $shipment->id,
            'documents' => $requiredDocuments,
        ]);

        return $shipment->fresh();
    }

    /**
     * Submit documents for shipment
     */
    public function submitDocuments(
        Shipment $shipment,
        array $documents,
        ?User $user = null
    ): Shipment {
        $existingDocs = $shipment->customs_documents ?? [];
        
        $shipment->update([
            'customs_documents' => array_merge($existingDocs, $documents),
            'customs_documents_submitted_at' => now(),
            'customs_status' => self::STATUS_PENDING,
            'customs_metadata' => array_merge(
                $shipment->customs_metadata ?? [],
                [
                    'documents_submitted' => array_keys($documents),
                    'submitted_at' => now()->toISOString(),
                    'submitted_by' => $user?->id,
                ]
            ),
        ]);

        Log::info('Customs documents submitted', [
            'shipment_id' => $shipment->id,
            'document_count' => count($documents),
        ]);

        return $shipment->fresh();
    }

    /**
     * Record inspection result
     */
    public function recordInspection(
        Shipment $shipment,
        string $result,
        ?string $notes = null,
        ?array $findings = null,
        ?User $inspector = null
    ): Shipment {
        $shipment->update([
            'customs_inspection_result' => $result,
            'customs_inspection_notes' => $notes,
            'customs_inspection_findings' => $findings,
            'customs_inspection_at' => now(),
            'customs_inspector_id' => $inspector?->id,
            'customs_status' => $result === 'passed' ? self::STATUS_PENDING : self::STATUS_HELD,
            'customs_metadata' => array_merge(
                $shipment->customs_metadata ?? [],
                [
                    'inspection' => [
                        'result' => $result,
                        'notes' => $notes,
                        'findings' => $findings,
                        'inspector_id' => $inspector?->id,
                        'at' => now()->toISOString(),
                    ],
                ]
            ),
        ]);

        Log::info('Customs inspection recorded', [
            'shipment_id' => $shipment->id,
            'result' => $result,
        ]);

        return $shipment->fresh();
    }

    /**
     * Record duty assessment
     */
    public function assessDuty(
        Shipment $shipment,
        float $dutyAmount,
        string $currency = 'USD',
        ?string $hsCode = null,
        ?float $taxAmount = null,
        ?User $user = null
    ): Shipment {
        $totalDue = $dutyAmount + ($taxAmount ?? 0);

        $shipment->update([
            'customs_status' => self::STATUS_DUTY_REQUIRED,
            'customs_duty_amount' => $dutyAmount,
            'customs_tax_amount' => $taxAmount,
            'customs_total_due' => $totalDue,
            'customs_duty_currency' => $currency,
            'customs_hs_code' => $hsCode,
            'customs_duty_assessed_at' => now(),
            'customs_metadata' => array_merge(
                $shipment->customs_metadata ?? [],
                [
                    'duty_assessment' => [
                        'duty' => $dutyAmount,
                        'tax' => $taxAmount,
                        'total' => $totalDue,
                        'currency' => $currency,
                        'hs_code' => $hsCode,
                        'assessed_at' => now()->toISOString(),
                        'assessed_by' => $user?->id,
                    ],
                ]
            ),
        ]);

        // TODO: Send duty payment notification

        Log::info('Customs duty assessed', [
            'shipment_id' => $shipment->id,
            'duty_amount' => $dutyAmount,
            'total_due' => $totalDue,
        ]);

        return $shipment->fresh();
    }

    /**
     * Record duty payment
     */
    public function recordDutyPayment(
        Shipment $shipment,
        float $amount,
        string $paymentMethod,
        string $paymentReference,
        ?User $user = null
    ): Shipment {
        $totalDue = $shipment->customs_total_due ?? 0;
        $isPaid = $amount >= $totalDue;

        $shipment->update([
            'customs_duty_paid' => $amount,
            'customs_duty_payment_method' => $paymentMethod,
            'customs_duty_payment_reference' => $paymentReference,
            'customs_duty_paid_at' => now(),
            'customs_status' => $isPaid ? self::STATUS_PENDING : self::STATUS_DUTY_REQUIRED,
            'customs_metadata' => array_merge(
                $shipment->customs_metadata ?? [],
                [
                    'duty_payment' => [
                        'amount' => $amount,
                        'method' => $paymentMethod,
                        'reference' => $paymentReference,
                        'paid_at' => now()->toISOString(),
                        'recorded_by' => $user?->id,
                    ],
                ]
            ),
        ]);

        Log::info('Customs duty payment recorded', [
            'shipment_id' => $shipment->id,
            'amount' => $amount,
            'is_fully_paid' => $isPaid,
        ]);

        return $shipment->fresh();
    }

    /**
     * Clear shipment through customs
     */
    public function clearShipment(
        Shipment $shipment,
        ?string $clearanceNumber = null,
        ?User $user = null,
        array $metadata = []
    ): Shipment {
        DB::transaction(function () use ($shipment, $clearanceNumber, $user, $metadata) {
            $shipment->update([
                'customs_status' => self::STATUS_CLEARED,
                'customs_clearance_number' => $clearanceNumber,
                'customs_cleared_at' => now(),
                'customs_cleared_by' => $user?->id,
                'customs_metadata' => array_merge(
                    $shipment->customs_metadata ?? [],
                    $metadata,
                    [
                        'clearance' => [
                            'number' => $clearanceNumber,
                            'cleared_at' => now()->toISOString(),
                            'cleared_by' => $user?->id,
                        ],
                    ]
                ),
            ]);

            // Transition shipment status
            $this->lifecycleService->transition(
                $shipment,
                ShipmentStatus::CUSTOMS_CLEARED,
                [
                    'performed_by' => $user?->id,
                    'trigger' => 'customs_cleared',
                    'clearance_number' => $clearanceNumber,
                ]
            );
        });

        Log::info('Shipment cleared through customs', [
            'shipment_id' => $shipment->id,
            'clearance_number' => $clearanceNumber,
        ]);

        return $shipment->fresh();
    }

    /**
     * Get shipments pending customs clearance
     */
    public function getPendingClearance(?int $branchId = null): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::with(['customer:id,company_name', 'originBranch:id,name', 'destBranch:id,name'])
            ->whereIn('customs_status', [
                self::STATUS_PENDING,
                self::STATUS_DOCUMENTS_REQUIRED,
                self::STATUS_UNDER_INSPECTION,
                self::STATUS_DUTY_REQUIRED,
                self::STATUS_HELD,
            ])
            ->when($branchId, fn($q) => $q->where('dest_branch_id', $branchId))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get shipments awaiting duty payment
     */
    public function getAwaitingDutyPayment(?int $branchId = null): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::with(['customer:id,company_name'])
            ->where('customs_status', self::STATUS_DUTY_REQUIRED)
            ->when($branchId, fn($q) => $q->where('dest_branch_id', $branchId))
            ->orderBy('customs_duty_assessed_at')
            ->get();
    }

    /**
     * Get customs clearance summary
     */
    public function getClearanceSummary(?int $branchId = null): array
    {
        $baseQuery = Shipment::query()
            ->when($branchId, fn($q) => $q->where('dest_branch_id', $branchId));

        return [
            'pending' => (clone $baseQuery)->where('customs_status', self::STATUS_PENDING)->count(),
            'documents_required' => (clone $baseQuery)->where('customs_status', self::STATUS_DOCUMENTS_REQUIRED)->count(),
            'under_inspection' => (clone $baseQuery)->where('customs_status', self::STATUS_UNDER_INSPECTION)->count(),
            'duty_required' => (clone $baseQuery)->where('customs_status', self::STATUS_DUTY_REQUIRED)->count(),
            'held' => (clone $baseQuery)->where('customs_status', self::STATUS_HELD)->count(),
            'cleared_today' => (clone $baseQuery)
                ->where('customs_status', self::STATUS_CLEARED)
                ->whereDate('customs_cleared_at', today())
                ->count(),
            'total_duty_pending' => (clone $baseQuery)
                ->where('customs_status', self::STATUS_DUTY_REQUIRED)
                ->sum('customs_total_due'),
        ];
    }

    /**
     * Get common required documents list
     */
    public static function getCommonDocuments(): array
    {
        return [
            'commercial_invoice' => 'Commercial Invoice',
            'packing_list' => 'Packing List',
            'bill_of_lading' => 'Bill of Lading / Airway Bill',
            'certificate_of_origin' => 'Certificate of Origin',
            'import_license' => 'Import License',
            'phytosanitary_certificate' => 'Phytosanitary Certificate',
            'insurance_certificate' => 'Insurance Certificate',
            'letter_of_credit' => 'Letter of Credit',
            'inspection_certificate' => 'Pre-Shipment Inspection Certificate',
            'customs_declaration' => 'Customs Declaration Form',
            'id_copy' => 'ID/Passport Copy',
            'power_of_attorney' => 'Power of Attorney',
        ];
    }
}
