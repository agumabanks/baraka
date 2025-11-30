<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ScanEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RealTimeTrackingService
{
    /**
     * Get real-time tracking data for a shipment
     */
    public function getTrackingData(Shipment $shipment): array
    {
        return Cache::remember("shipment_tracking_{$shipment->id}", 60, function () use ($shipment) {
            $shipment->load([
                'scanEvents' => fn($q) => $q->latest()->limit(50),
                'originBranch',
                'destBranch',
                'customer',
                'assignedWorker'
            ]);

            return [
                'shipment' => [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->status,
                    'current_location' => $this->getCurrentLocation($shipment),
                    'origin' => $shipment->originBranch?->name,
                    'destination' => $shipment->destBranch?->name,
                    'customer' => $shipment->customer?->name,
                    'expected_delivery' => $shipment->expected_delivery_date?->format('Y-m-d H:i:s'),
                    'progress_percentage' => $this->calculateProgress($shipment),
                ],
                'timeline' => $this->buildTimeline($shipment),
                'current_position' => $this->getCurrentPosition($shipment),
                'eta' => $this->calculateETA($shipment),
                'scan_events' => $shipment->scanEvents->map(fn($event) => [
                    'type' => $event->type,
                    'location' => $event->location,
                    'timestamp' => $event->created_at->format('Y-m-d H:i:s'),
                    'description' => $event->description,
                    'coordinates' => $event->coordinates ?? null,
                ]),
            ];
        });
    }

    /**
     * Get current location description
     */
    private function getCurrentLocation(Shipment $shipment): ?string
    {
        $lastScan = $shipment->scanEvents->first();
        
        if (!$lastScan) {
            return $shipment->originBranch?->name;
        }

        return $lastScan->location ?? $lastScan->branch?->name ?? 'In Transit';
    }

    /**
     * Calculate delivery progress percentage
     */
    private function calculateProgress(Shipment $shipment): int
    {
        $statusProgress = [
            'created' => 10,
            'picked_up' => 25,
            'processing' => 40,
            'in_transit' => 60,
            'out_for_delivery' => 85,
            'delivered' => 100,
            'cancelled' => 0,
            'returned' => 0,
        ];

        return $statusProgress[$shipment->status] ?? 0;
    }

    /**
     * Build shipment timeline
     */
    private function buildTimeline(Shipment $shipment): array
    {
        $timeline = [];

        $milestones = [
            'created' => ['label' => 'Shipment Created', 'timestamp' => $shipment->created_at],
            'picked_up' => ['label' => 'Picked Up', 'timestamp' => $shipment->picked_up_at],
            'in_transit' => ['label' => 'In Transit', 'timestamp' => $shipment->transferred_at],
            'out_for_delivery' => ['label' => 'Out for Delivery', 'timestamp' => $shipment->out_for_delivery_at],
            'delivered' => ['label' => 'Delivered', 'timestamp' => $shipment->delivered_at],
        ];

        foreach ($milestones as $status => $data) {
            $timeline[] = [
                'status' => $status,
                'label' => $data['label'],
                'timestamp' => $data['timestamp']?->format('Y-m-d H:i:s'),
                'completed' => $data['timestamp'] !== null,
            ];
        }

        return $timeline;
    }

    /**
     * Get current GPS position if available
     */
    private function getCurrentPosition(Shipment $shipment): ?array
    {
        $lastScanWithCoords = $shipment->scanEvents()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->first();

        if (!$lastScanWithCoords) {
            // Fallback to branch coordinates
            $currentBranch = $this->getCurrentBranch($shipment);
            if ($currentBranch && $currentBranch->latitude && $currentBranch->longitude) {
                return [
                    'lat' => (float) $currentBranch->latitude,
                    'lng' => (float) $currentBranch->longitude,
                    'type' => 'branch',
                ];
            }
            return null;
        }

        return [
            'lat' => (float) $lastScanWithCoords->latitude,
            'lng' => (float) $lastScanWithCoords->longitude,
            'type' => 'gps',
            'timestamp' => $lastScanWithCoords->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get current branch location
     */
    private function getCurrentBranch(Shipment $shipment)
    {
        // Logic to determine current branch based on status
        if (in_array($shipment->status, ['created', 'picked_up'])) {
            return $shipment->originBranch;
        }

        if ($shipment->status === 'delivered') {
            return $shipment->destBranch;
        }

        // In transit - could be at transfer hub or between locations
        return $shipment->transfer_hub_id 
            ? \App\Models\Backend\Hub::find($shipment->transfer_hub_id)
            : $shipment->originBranch;
    }

    /**
     * Calculate estimated time of arrival
     */
    private function calculateETA(Shipment $shipment): ?array
    {
        if ($shipment->status === 'delivered') {
            return [
                'estimated' => null,
                'actual' => $shipment->delivered_at?->format('Y-m-d H:i:s'),
            ];
        }

        if ($shipment->expected_delivery_date) {
            return [
                'estimated' => $shipment->expected_delivery_date->format('Y-m-d H:i:s'),
                'confidence' => $this->calculateETAConfidence($shipment),
            ];
        }

        // Calculate ETA based on average transit times
        $averageTransitTime = $this->getAverageTransitTime(
            $shipment->origin_branch_id,
            $shipment->dest_branch_id
        );

        if ($averageTransitTime) {
            $eta = $shipment->created_at->addHours($averageTransitTime);
            return [
                'estimated' => $eta->format('Y-m-d H:i:s'),
                'confidence' => 'medium',
            ];
        }

        return null;
    }

    /**
     * Calculate ETA confidence level
     */
    private function calculateETAConfidence(Shipment $shipment): string
    {
        $now = Carbon::now();
        $eta = $shipment->expected_delivery_date;

        if (!$eta) {
            return 'low';
        }

        $progress = $this->calculateProgress($shipment);

        // High confidence if far along and on schedule
        if ($progress >= 60 && $now->lte($eta)) {
            return 'high';
        }

        // Medium confidence if on track
        if ($progress >= 40 && $now->lte($eta->addHours(24))) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get average transit time between branches
     */
    private function getAverageTransitTime(int $originBranchId, int $destBranchId): ?float
    {
        return Cache::remember(
            "avg_transit_{$originBranchId}_{$destBranchId}",
            3600,
            function () use ($originBranchId, $destBranchId) {
                $avg = Shipment::where('origin_branch_id', $originBranchId)
                    ->where('dest_branch_id', $destBranchId)
                    ->whereNotNull('delivered_at')
                    ->whereNotNull('created_at')
                    ->where('created_at', '>=', now()->subMonths(3))
                    ->get()
                    ->map(fn($s) => $s->created_at->diffInHours($s->delivered_at))
                    ->avg();

                return $avg ? round($avg, 2) : null;
            }
        );
    }

    /**
     * Get multiple shipments tracking data (for dashboard)
     */
    public function getMultipleShipmentsTracking(array $shipmentIds): array
    {
        $shipments = Shipment::with(['originBranch', 'destBranch', 'scanEvents' => fn($q) => $q->latest()->limit(1)])
            ->whereIn('id', $shipmentIds)
            ->get();

        return $shipments->map(function ($shipment) {
            $lastScan = $shipment->scanEvents->first();
            
            return [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status,
                'progress' => $this->calculateProgress($shipment),
                'current_location' => $this->getCurrentLocation($shipment),
                'last_update' => $lastScan?->created_at?->diffForHumans(),
                'position' => $this->getCurrentPosition($shipment),
            ];
        })->toArray();
    }

    /**
     * Invalidate tracking cache
     */
    public function invalidateCache(Shipment $shipment): void
    {
        Cache::forget("shipment_tracking_{$shipment->id}");
    }
}
