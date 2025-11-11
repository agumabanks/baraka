<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\Backend\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileScanningController extends Controller
{
    /**
     * Lightweight scan API for mobile devices
     * Optimized for low bandwidth and offline support
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string',
            'action' => 'required|in:inbound,outbound,delivery,exception,manual_intervention',
            'location_id' => 'required|integer',
            'timestamp' => 'required|date_format:Y-m-d H:i:s',
            'notes' => 'nullable|string|max:500',
            'offline_sync_key' => 'nullable|string|unique:scans,offline_sync_key',
        ]);

        try {
            $shipment = Shipment::where('tracking_number', $validated['tracking_number'])
                ->firstOrFail();

            $branch = Branch::findOrFail($validated['location_id']);

            // Record scan event
            $scan = DB::table('scans')->insertGetId([
                'shipment_id' => $shipment->id,
                'branch_id' => $branch->id,
                'action' => $validated['action'],
                'timestamp' => $validated['timestamp'],
                'notes' => $validated['notes'],
                'offline_sync_key' => $validated['offline_sync_key'],
                'device_id' => $request->header('X-Device-ID'),
                'created_at' => now(),
            ]);

            // Update shipment status
            $shipment->update([
                'current_status' => $this->mapActionToStatus($validated['action']),
                'current_location_id' => $branch->id,
                'last_scanned_at' => $validated['timestamp'],
            ]);

            // Broadcast event
            broadcast(new \App\Events\ShipmentScanned($shipment, $branch))->toOthers();

            // Queue webhook if configured
            if (config('app.env') === 'production') {
                dispatch(new \App\Jobs\TriggerWebhook('shipment.scanned', [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->current_status,
                    'location' => $branch->name,
                ]));
            }

            Log::info('Mobile scan recorded', [
                'tracking_number' => $shipment->tracking_number,
                'action' => $validated['action'],
                'branch' => $branch->code,
                'device_id' => $request->header('X-Device-ID'),
            ]);

            return response()->json([
                'success' => true,
                'scan_id' => $scan,
                'shipment_id' => $shipment->id,
                'status' => $shipment->current_status,
                'next_expected' => $this->getNextExpectedAction($shipment),
            ]);
        } catch (\Throwable $e) {
            Log::error('Mobile scan failed', [
                'error' => $e->getMessage(),
                'tracking' => $validated['tracking_number'] ?? null,
                'device_id' => $request->header('X-Device-ID'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Scan failed',
                'offline_sync_key' => $validated['offline_sync_key'] ?? null,
            ], 400);
        }
    }

    /**
     * Bulk scan operation for multiple shipments
     * Returns minimal data for bandwidth efficiency
     */
    public function bulkScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scans' => 'required|array|min:1|max:100',
            'scans.*.tracking_number' => 'required|string',
            'scans.*.action' => 'required|in:inbound,outbound,delivery,exception',
            'scans.*.location_id' => 'required|integer',
            'scans.*.offline_sync_key' => 'nullable|string',
        ]);

        $results = ['success' => [], 'failed' => []];

        foreach ($validated['scans'] as $scanData) {
            try {
                $shipment = Shipment::where('tracking_number', $scanData['tracking_number'])
                    ->first();

                if (!$shipment) {
                    $results['failed'][] = [
                        'tracking' => $scanData['tracking_number'],
                        'error' => 'Shipment not found',
                    ];
                    continue;
                }

                $shipment->update([
                    'current_status' => $this->mapActionToStatus($scanData['action']),
                    'current_location_id' => $scanData['location_id'],
                ]);

                $results['success'][] = [
                    'tracking' => $scanData['tracking_number'],
                    'status' => $shipment->current_status,
                    'offline_sync_key' => $scanData['offline_sync_key'] ?? null,
                ];
            } catch (\Throwable $e) {
                $results['failed'][] = [
                    'tracking' => $scanData['tracking_number'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($results['failed']) === 0,
            'results' => $results,
            'processed' => count($results['success']),
            'failed' => count($results['failed']),
        ]);
    }

    /**
     * Get offline sync queue
     * Returns pending scans that need syncing
     */
    public function getOfflineSyncQueue(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');

        $pendingScans = DB::table('scans')
            ->where('device_id', $deviceId)
            ->where('synced_at', null)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'pending_sync' => $pendingScans->count(),
            'scans' => $pendingScans->map(fn($scan) => [
                'id' => $scan->id,
                'tracking_number' => $scan->tracking_number,
                'action' => $scan->action,
                'offline_sync_key' => $scan->offline_sync_key,
            ]),
        ]);
    }

    /**
     * Confirm offline sync
     * Mark scans as successfully synced
     */
    public function confirmSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scan_ids' => 'required|array|min:1',
            'scan_ids.*' => 'integer|exists:scans,id',
        ]);

        DB::table('scans')
            ->whereIn('id', $validated['scan_ids'])
            ->update(['synced_at' => now()]);

        return response()->json([
            'success' => true,
            'synced_count' => count($validated['scan_ids']),
        ]);
    }

    /**
     * Get shipment details (minimal - for mobile)
     * Returns only essential data to minimize bandwidth
     */
    public function getShipmentDetails(string $trackingNumber): JsonResponse
    {
        try {
            $shipment = Shipment::where('tracking_number', $trackingNumber)
                ->select(['id', 'tracking_number', 'current_status', 'current_location_id', 'last_scanned_at'])
                ->firstOrFail();

            return response()->json([
                'id' => $shipment->id,
                'tracking' => $shipment->tracking_number,
                'status' => $shipment->current_status,
                'location_id' => $shipment->current_location_id,
                'last_scan' => $shipment->last_scanned_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Shipment not found',
            ], 404);
        }
    }

    private function mapActionToStatus(string $action): string
    {
        return match ($action) {
            'inbound' => ShipmentStatus::IN_TRANSIT->value,
            'outbound' => ShipmentStatus::OUT_FOR_DELIVERY->value,
            'delivery' => ShipmentStatus::DELIVERED->value,
            'exception' => ShipmentStatus::EXCEPTION->value,
            'manual_intervention' => ShipmentStatus::ON_HOLD->value,
            default => ShipmentStatus::PENDING->value,
        };
    }

    private function getNextExpectedAction(Shipment $shipment): ?string
    {
        return match ($shipment->current_status) {
            ShipmentStatus::PENDING->value => 'inbound',
            ShipmentStatus::IN_TRANSIT->value => 'outbound',
            ShipmentStatus::OUT_FOR_DELIVERY->value => 'delivery',
            default => null,
        };
    }
}
