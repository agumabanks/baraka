<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentScanned;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Device;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EnhancedMobileScanningController extends Controller
{
    /**
     * Enhanced mobile scan API with advanced security and validation
     * Optimized for low bandwidth and offline support with device tracking
     */
    public function scan(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');
        $deviceToken = $request->header('X-Device-Token');
        $appVersion = $request->header('X-App-Version', '1.0.0');

        // Enhanced validation
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:50',
            'action' => [
                'required',
                'string',
                Rule::in(['inbound', 'outbound', 'delivery', 'exception', 'manual_intervention', 'handoff', 'pickup', 'arrival', 'departure'])
            ],
            'location_id' => 'required|integer|exists:branches,id',
            'timestamp' => 'required|date_format:Y-m-d H:i:s',
            'notes' => 'nullable|string|max:500',
            'offline_sync_key' => 'nullable|string|unique:scans,offline_sync_key',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'barcode_type' => 'nullable|string|in:qr,barcode,sscc,sscc18',
            'batch_id' => 'nullable|string|max:50',
        ]);

        try {
            // Device authentication and rate limiting
            $this->validateDevice($deviceId, $deviceToken);
            $this->checkRateLimit($deviceId, 'scan');

            $shipment = Shipment::where('tracking_number', $validated['tracking_number'])
                ->firstOrFail();

            $branch = Branch::findOrFail($validated['location_id']);

            // Check for duplicate scan within time window
            $duplicateCheck = DB::table('scans')
                ->where('shipment_id', $shipment->id)
                ->where('action', $validated['action'])
                ->where('created_at', '>', now()->subMinutes(5))
                ->first();

            if ($duplicateCheck) {
                return response()->json([
                    'success' => false,
                    'error' => 'Duplicate scan detected',
                    'scan_id' => $duplicateCheck->id,
                    'offline_sync_key' => $validated['offline_sync_key'] ?? null,
                ], 409);
            }

            // Record scan event with enhanced data
            $scanData = [
                'shipment_id' => $shipment->id,
                'branch_id' => $branch->id,
                'tracking_number' => $shipment->tracking_number,
                'action' => $validated['action'],
                'timestamp' => $validated['timestamp'],
            'notes' => $validated['notes'] ?? null,
            'offline_sync_key' => $validated['offline_sync_key'] ?? null,
                'device_id' => $deviceId,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'accuracy' => $validated['accuracy'] ?? null,
                'barcode_type' => $validated['barcode_type'] ?? null,
                'batch_id' => $validated['batch_id'] ?? null,
                'app_version' => $appVersion,
            ];

            $scan = $this->persistScanRecord($scanData);

            // Update shipment status with workflow automation
            $newStatus = $this->mapActionToStatus($validated['action']);
            $previousStatus = $this->stringifyStatus($shipment->current_status);

            $shipment->update([
                'current_status' => $newStatus,
                'current_location_id' => $branch->id,
            ]);

            $shipment->refresh();

            // Trigger workflow automation
            $this->triggerWorkflowAutomation($shipment, $validated['action'], $validated);

            // Broadcast real-time event
            $eventPayload = [
                'device_id' => $deviceId,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'batch_id' => $validated['batch_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            broadcast(new ShipmentScanned($shipment, $branch, $validated['action'], $eventPayload))->toOthers();

            // Queue webhook if configured and job exists
            if (config('app.env') === 'production' && class_exists(\App\Jobs\TriggerWebhook::class)) {
                dispatch(new \App\Jobs\TriggerWebhook('shipment.scanned', [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $newStatus,
                    'previous_status' => $previousStatus,
                    'action' => $validated['action'],
                    'location' => $branch->name,
                    'device_id' => $deviceId,
                ]));
            }

            Log::info('Enhanced mobile scan recorded', [
                'scan_id' => $scan,
                'tracking_number' => $shipment->tracking_number,
                'action' => $validated['action'],
                'branch' => $branch->code,
                'device_id' => $deviceId,
                'batch_id' => $validated['batch_id'] ?? null,
                'has_location' => !empty($validated['latitude']) && !empty($validated['longitude']),
            ]);

            return response()->json([
                'success' => true,
                'scan_id' => $scan,
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
                'previous_status' => $previousStatus,
                'next_expected' => $this->getNextExpectedAction($shipment),
                'workflow_suggestions' => $this->getWorkflowSuggestions($shipment, $validated['action']),
                'branch_info' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                ],
            ]);
        } catch (BadRequestHttpException $e) {
            Log::warning('Enhanced mobile scan validation failed', [
                'error' => $e->getMessage(),
                'tracking' => $validated['tracking_number'] ?? null,
                'device_id' => $deviceId,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'offline_sync_key' => $validated['offline_sync_key'] ?? null,
            ], 400);
        } catch (UnauthorizedHttpException $e) {
            Log::warning('Enhanced mobile scan unauthorized', [
                'error' => $e->getMessage(),
                'tracking' => $validated['tracking_number'] ?? null,
                'device_id' => $deviceId,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'offline_sync_key' => $validated['offline_sync_key'] ?? null,
            ], 401);
        } catch (TooManyRequestsHttpException $e) {
            Log::notice('Enhanced mobile scan rate limited', [
                'device_id' => $deviceId,
                'action' => $validated['action'] ?? null,
            ]);

            $errorMessage = 'Rate limit exceeded for ' . ($validated['action'] ?? 'scan');
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'message' => $errorMessage,
                'retry_after' => $retryAfter,
                'offline_sync_key' => $validated['offline_sync_key'] ?? null,
            ], 429);
        } catch (ModelNotFoundException $e) {
            $model = class_basename($e->getModel());
            $message = $model === 'Shipment' ? 'Shipment not found' : 'Resource not found';

            Log::warning('Enhanced mobile scan resource missing', [
                'model' => $model,
                'tracking' => $validated['tracking_number'] ?? null,
                'device_id' => $deviceId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Scan failed: ' . $message,
                'offline_sync_key' => $validated['offline_sync_key'] ?? null,
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Enhanced mobile scan failed', [
                'error' => $e->getMessage(),
                'tracking' => $validated['tracking_number'] ?? null,
                'device_id' => $deviceId,
                'action' => $validated['action'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Scan failed: ' . $e->getMessage(),
                'offline_sync_key' => $validated['offline_sync_key'] ?? null,
            ], 400);
        }
    }

    /**
     * Enhanced bulk scan with conflict resolution
     */
    public function bulkScan(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');
        $deviceToken = $request->header('X-Device-Token');

        $validated = $request->validate([
            'scans' => 'required|array|min:1|max:100',
            'scans.*.tracking_number' => 'required|string|max:50',
            'scans.*.action' => 'required|string|in:inbound,outbound,delivery,exception,manual_intervention',
            'scans.*.location_id' => 'required|integer|exists:branches,id',
            'scans.*.offline_sync_key' => 'nullable|string',
            'scans.*.latitude' => 'nullable|numeric|between:-90,90',
            'scans.*.longitude' => 'nullable|numeric|between:-180,180',
            'batch_id' => 'required|string|max:50',
        ]);

        try {
            $this->validateDevice($deviceId, $deviceToken);
            $this->checkRateLimit($deviceId, 'bulk_scan', count($validated['scans']));

            $results = ['success' => [], 'failed' => [], 'conflicts' => []];

            foreach ($validated['scans'] as $index => $scanData) {
                try {
                    $shipment = Shipment::where('tracking_number', $scanData['tracking_number'])->first();

                    if (!$shipment) {
                        $results['failed'][] = [
                            'index' => $index,
                            'tracking' => $scanData['tracking_number'],
                            'error' => 'Shipment not found',
                        ];
                        continue;
                    }

                    // Check for conflicts
                    $conflict = $this->checkForConflicts($shipment, $scanData);
                    if ($conflict) {
                        $results['conflicts'][] = [
                            'index' => $index,
                            'tracking' => $scanData['tracking_number'],
                            'conflict_type' => $conflict['type'],
                            'message' => $conflict['message'],
                        ];
                        continue;
                    }

                    // Process scan
                    $newStatus = $this->mapActionToStatus($scanData['action']);
                    $shipment->update([
                        'current_status' => $newStatus,
                        'current_location_id' => $scanData['location_id'],
                    ]);

                    $this->persistScanRecord([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $scanData['location_id'],
                        'tracking_number' => $shipment->tracking_number,
                        'action' => $scanData['action'],
                        'timestamp' => now()->toDateTimeString(),
                        'offline_sync_key' => $scanData['offline_sync_key'] ?? null,
                        'device_id' => $deviceId,
                        'batch_id' => $validated['batch_id'],
                    ]);

                    $results['success'][] = [
                        'index' => $index,
                        'tracking' => $scanData['tracking_number'],
                        'status' => $newStatus,
                        'offline_sync_key' => $scanData['offline_sync_key'] ?? null,
                    ];
                } catch (\Throwable $e) {
                    $results['failed'][] = [
                        'index' => $index,
                        'tracking' => $scanData['tracking_number'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => count($results['failed']) === 0 && count($results['conflicts']) === 0,
                'results' => $results,
                'processed' => count($results['success']),
                'failed' => count($results['failed']),
                'conflicts' => count($results['conflicts']),
                'batch_id' => $validated['batch_id'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Bulk scan failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Enhanced offline sync with conflict resolution
     */
    public function enhancedOfflineSync(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');
        $deviceToken = $request->header('X-Device-Token');

        $validated = $request->validate([
            'pending_scans' => 'required|array|min:1|max:500',
            'pending_scans.*.offline_sync_key' => 'required|string|max:100',
            'pending_scans.*.action' => 'required|string|in:inbound,outbound,delivery,exception',
            'pending_scans.*.location_id' => 'required|integer|exists:branches,id',
            'pending_scans.*.tracking_number' => 'required|string',
            'pending_scans.*.timestamp' => 'required|date_format:Y-m-d H:i:s',
        ]);

        try {
            $this->validateDevice($deviceId, $deviceToken);

            $results = ['processed' => [], 'conflicts' => [], 'errors' => []];

            foreach ($validated['pending_scans'] as $scanData) {
                try {
                    // Check for existing scan with same sync key
                    $existingScan = DB::table('scans')
                        ->where('offline_sync_key', $scanData['offline_sync_key'])
                        ->where('device_id', $deviceId)
                        ->first();

                    if ($existingScan && $existingScan->synced_at) {
                        $results['processed'][] = [
                            'sync_key' => $scanData['offline_sync_key'],
                            'status' => 'already_synced',
                            'scan_id' => $existingScan->id,
                        ];
                        continue;
                    }

                    $shipment = Shipment::where('tracking_number', $scanData['tracking_number'])->first();

                    if (!$shipment) {
                        $results['errors'][] = [
                            'sync_key' => $scanData['offline_sync_key'],
                            'error' => 'Shipment not found',
                        ];
                        continue;
                    }

                    // Check for conflicts
                    $recentScans = DB::table('scans')
                        ->where('shipment_id', $shipment->id)
                        ->where('created_at', '>', now()->subMinutes(30))
                        ->count();

                    if ($recentScans > 0) {
                        $results['conflicts'][] = [
                            'sync_key' => $scanData['offline_sync_key'],
                            'tracking' => $scanData['tracking_number'],
                            'conflict_type' => 'recent_activity',
                            'message' => 'Recent scan activity detected',
                        ];
                        continue;
                    }

                    // Process the scan
                    $newStatus = $this->mapActionToStatus($scanData['action']);
                    $shipment->update([
                        'current_status' => $newStatus,
                        'current_location_id' => $scanData['location_id'],
                    ]);

                    if ($existingScan) {
                        DB::table('scans')
                            ->where('id', $existingScan->id)
                            ->update([
                                'action' => $scanData['action'],
                                'timestamp' => $scanData['timestamp'],
                                'synced_at' => now(),
                                'updated_at' => now(),
                            ]);
                    } else {
                        $this->persistScanRecord([
                            'shipment_id' => $shipment->id,
                            'branch_id' => $scanData['location_id'],
                            'tracking_number' => $shipment->tracking_number,
                            'action' => $scanData['action'],
                            'timestamp' => $scanData['timestamp'],
                            'offline_sync_key' => $scanData['offline_sync_key'],
                            'device_id' => $deviceId,
                            'synced_at' => now(),
                        ]);
                    }

                    $results['processed'][] = [
                        'sync_key' => $scanData['offline_sync_key'],
                        'status' => 'synced',
                        'tracking' => $scanData['tracking_number'],
                    ];

                } catch (\Throwable $e) {
                    $results['errors'][] = [
                        'sync_key' => $scanData['offline_sync_key'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => count($results['errors']) === 0,
                'results' => $results,
                'sync_count' => count($results['processed']),
                'conflict_count' => count($results['conflicts']),
                'error_count' => count($results['errors']),
                'next_sync' => $this->getNextSyncTime(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Register a new device
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:191|unique:devices,device_id',
            'device_name' => 'required|string|max:100',
            'platform' => 'required|string|in:android,ios,web',
            'app_version' => 'required|string|max:20',
            'fcm_token' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $userId = $validated['user_id'] ?? $request->user()?->id;

            if (!$userId) {
                $userId = User::query()->value('id');
            }

            if (!$userId) {
                throw new BadRequestHttpException('Unable to associate device with a user');
            }

            $device = Device::create([
                'user_id' => $userId,
                'platform' => $validated['platform'],
                'device_uuid' => Str::uuid()->toString(),
                'device_id' => $validated['device_id'],
                'device_name' => $validated['device_name'],
                'app_version' => $validated['app_version'],
                'device_token' => bin2hex(random_bytes(32)), // Generate secure token
                'fcm_token' => $validated['fcm_token'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'device_id' => $device->device_id,
                'device_token' => $device->device_token,
                'message' => 'Device registered successfully',
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Device registration failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Authenticate device and get access token
     */
    public function authenticateDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'device_token' => 'required|string',
        ]);

        try {
            $device = Device::where('device_id', $validated['device_id'])
                ->where('device_token', $validated['device_token'])
                ->first();

            if (!$device || !$device->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid device credentials',
                ], 401);
            }

            // Update last seen
            $device->update(['last_seen_at' => now()]);

            return response()->json([
                'success' => true,
                'authenticated' => true,
                'device' => [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'platform' => $device->platform,
                    'app_version' => $device->app_version,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deactivate device
     */
    public function deactivateDevice(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');
        $deviceToken = $request->header('X-Device-Token');

        try {
            $device = Device::where('device_id', $deviceId)
                ->where('device_token', $deviceToken)
                ->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'error' => 'Device not found',
                ], 404);
            }

            $device->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Device deactivated successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Deactivation failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');
        $deviceToken = $request->header('X-Device-Token');

        try {
            $this->validateDevice($deviceId, $deviceToken);

            $device = Device::where('device_id', $deviceId)->first();

            return response()->json([
                'success' => true,
                'device' => [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'platform' => $device->platform,
                    'app_version' => $device->app_version,
                    'is_active' => $device->is_active,
                    'last_seen_at' => $device->last_seen_at?->toISOString(),
                    'created_at' => $device->created_at->toISOString(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    // Legacy methods from original controller
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

    public function getOfflineSyncQueue(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');

        $pendingScans = DB::table('scans')
            ->where('device_id', $deviceId)
            ->whereNull('synced_at')
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

    public function confirmSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scan_ids' => 'required|array|min:1',
            'scan_ids.*' => 'integer|exists:scans,id',
        ]);

        DB::table('scans')
            ->whereIn('id', $validated['scan_ids'])
            ->update([
                'synced_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'synced_count' => count($validated['scan_ids']),
        ]);
    }

    /**
     * Device authentication
     */
    private function validateDevice(?string $deviceId, ?string $deviceToken): void
    {
        if (!$deviceId || !$deviceToken) {
            throw new BadRequestHttpException('Missing device credentials');
        }

        $device = Device::where('device_id', $deviceId)
            ->where('device_token', $deviceToken)
            ->first();

        if (!$device) {
            throw new UnauthorizedHttpException('MobileScanning', 'Invalid device credentials');
        }

        if (!$device->is_active) {
            throw new UnauthorizedHttpException('MobileScanning', 'Device is inactive');
        }

        $device->update(['last_seen_at' => now()]);
    }

    /**
     * Rate limiting for devices
     */
    private function checkRateLimit(string $deviceId, string $action, int $quantity = 1): void
    {
        $limits = [
            'scan' => 100, // 100 scans per hour
            'bulk_scan' => 10, // 10 bulk scans per hour
        ];

        $limit = $limits[$action] ?? 50;
        $key = "device_rate_limit:{$deviceId}:{$action}";

        $current = Cache::get($key, 0);
        if ($current + $quantity > $limit) {
            throw new TooManyRequestsHttpException(60, "Rate limit exceeded for {$action}");
        }

        Cache::put($key, $current + $quantity, now()->addHour());
    }

    /**
     * Check for conflicts during bulk operations
     */
    private function checkForConflicts(Shipment $shipment, array $scanData): ?array
    {
        // Check for status conflicts
        $recentStatusChange = DB::table('scans')
            ->where('shipment_id', $shipment->id)
            ->where('created_at', '>', now()->subMinutes(10))
            ->first();

        if ($recentStatusChange) {
            return [
                'type' => 'status_conflict',
                'message' => 'Recent status change detected',
            ];
        }

        // Check for location conflicts
        if (!is_null($shipment->current_location_id) && $shipment->current_location_id != $scanData['location_id']) {
            $timeDiff = $shipment->updated_at?->diffInMinutes(now());
            if (!is_null($timeDiff) && $timeDiff < 30) {
                return [
                    'type' => 'location_conflict',
                    'message' => 'Shipment recently moved to different location',
                ];
            }
        }

        return null;
    }

    /**
     * Trigger workflow automation
     */
    private function persistScanRecord(array $data): int
    {
        $columns = Schema::getColumnListing('scans');
        $normalizedMap = [];

        foreach ($columns as $column) {
            $normalizedMap[$column] = $column;
            $normalizedMap[trim($column, " \"`'")] = $column;
        }

        $filtered = [];

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $normalizedMap)) {
                $filtered[$normalizedMap[$key]] = $value;
            }
        }

        return DB::table('scans')->insertGetId(array_merge(
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $filtered
        ));
    }

    private function stringifyStatus($status): string
    {
        if ($status instanceof ShipmentStatus) {
            return $status->value;
        }

        return (string) $status;
    }

    private function triggerWorkflowAutomation(Shipment $shipment, string $action, array $scanData): void
    {
        // Create workflow tasks based on scan events
        switch ($action) {
            case 'exception':
                dispatch(new \App\Jobs\CreateExceptionWorkflowTask($shipment, $scanData));
                break;
            case 'delivery':
                dispatch(new \App\Jobs\CreateDeliveryConfirmationWorkflowTask($shipment, $scanData));
                break;
            case 'manual_intervention':
                dispatch(new \App\Jobs\CreateManualInterventionWorkflowTask($shipment, $scanData));
                break;
        }
    }

    /**
     * Get workflow suggestions
     */
    private function getWorkflowSuggestions(Shipment $shipment, string $action): array
    {
        $suggestions = [];

        switch ($action) {
            case 'exception':
                $suggestions[] = 'Create exception report';
                $suggestions[] = 'Notify customer service';
                break;
            case 'delivery':
                $suggestions[] = 'Send delivery confirmation';
                $suggestions[] = 'Update customer portal';
                break;
        }

        return $suggestions;
    }

    private function mapActionToStatus(string $action): string
    {
        return match ($action) {
            'pickup' => ShipmentStatus::PICKED_UP->value,
            'inbound' => ShipmentStatus::AT_DESTINATION_HUB->value,
            'arrival' => ShipmentStatus::LINEHAUL_ARRIVED->value,
            'outbound' => ShipmentStatus::LINEHAUL_DEPARTED->value,
            'departure' => ShipmentStatus::LINEHAUL_DEPARTED->value,
            'handoff' => ShipmentStatus::OUT_FOR_DELIVERY->value,
            'delivery' => ShipmentStatus::DELIVERED->value,
            'exception', 'manual_intervention' => ShipmentStatus::EXCEPTION->value,
            default => ShipmentStatus::BOOKED->value,
        };
    }

    private function getNextExpectedAction(Shipment $shipment): ?string
    {
        $status = $this->stringifyStatus($shipment->current_status);

        return match ($status) {
            ShipmentStatus::BOOKED->value,
            ShipmentStatus::PICKUP_SCHEDULED->value => 'pickup',
            ShipmentStatus::PICKED_UP->value => 'inbound',
            ShipmentStatus::AT_ORIGIN_HUB->value,
            ShipmentStatus::BAGGED->value => 'departure',
            ShipmentStatus::LINEHAUL_DEPARTED->value => 'arrival',
            ShipmentStatus::LINEHAUL_ARRIVED->value,
            ShipmentStatus::AT_DESTINATION_HUB->value => 'handoff',
            ShipmentStatus::OUT_FOR_DELIVERY->value => 'delivery',
            default => null,
        };
    }

    private function getNextSyncTime(): string
    {
        return now()->addMinutes(15)->toISOString();
    }
}