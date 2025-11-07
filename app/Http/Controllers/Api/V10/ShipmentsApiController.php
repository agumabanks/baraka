<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Backend\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Services\Logistics\ShipmentLifecycleService;

class ShipmentsApiController extends Controller
{
    public function __construct(private ShipmentLifecycleService $lifecycleService)
    {
    }

    /**
     * Get paginated list of shipments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 50);
            $status = $request->input('status');
            $search = $request->input('search');

            $workerColumns = $this->resolveAssignedWorkerColumns();

            $query = Shipment::with([
                'originBranch:id,name,code',
                'destBranch:id,name,code',
                'destinationBranch:id,name,code',
                'assignedWorker' => function ($relation) use ($workerColumns) {
                    $relation->select($workerColumns);
                },
                'assignedWorker.user:id,name,email',
                'client:id,business_name',
                'customer:id,name',
            ])
            ->orderBy('created_at', 'desc');

            // Filter by status
            if ($status) {
                $statusEnum = ShipmentStatus::fromString((string) $status);

                if ($statusEnum) {
                    $query->where('current_status', $statusEnum->value);
                } else {
                    $query->where(function ($inner) use ($status) {
                        $inner->where('status', strtolower((string) $status))
                              ->orWhere('current_status', strtoupper((string) $status));
                    });
                }
            }

            // Search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tracking_number', 'like', "%{$search}%")
                      ->orWhereHas('client', function ($clientQuery) use ($search) {
                          $clientQuery->where('business_name', 'like', "%{$search}%");
                      });
                });
            }

            $shipments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $shipments->items(),
                'pagination' => [
                    'total' => $shipments->total(),
                    'per_page' => $shipments->perPage(),
                    'current_page' => $shipments->currentPage(),
                    'last_page' => $shipments->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ShipmentsApiController@index: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shipments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all clients for dropdown selection
     */
    public function getClients(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search');
            $perPage = $request->input('per_page', 100);

            $query = Client::with('primaryBranch:id,name')
                ->where('status', 'active')
                ->orderBy('business_name', 'asc');

            if ($search) {
                $query->where('business_name', 'like', "%{$search}%");
            }

            $clients = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $clients->items(),
                'pagination' => [
                    'total' => $clients->total(),
                    'has_more' => $clients->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ShipmentsApiController@getClients: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new client
     */
    public function createClient(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'primary_branch_id' => 'required|exists:branches,id',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $kycData = [
                'contact_name' => $request->input('contact_name'),
                'contact_phone' => $request->input('contact_phone'),
                'contact_email' => $request->input('contact_email'),
                'address' => $request->input('address'),
            ];

            $client = Client::create([
                'business_name' => $request->input('business_name'),
                'primary_branch_id' => $request->input('primary_branch_id'),
                'status' => 'active',
                'kyc_data' => $kycData,
            ]);

            $client->load('primaryBranch:id,name');

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => $client,
            ], 201);

        } catch (\Exception $e) {
            Log::error('ShipmentsApiController@createClient: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new shipment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_id' => 'nullable|exists:clients,id',
                'customer_id' => 'nullable|exists:users,id',
                'origin_branch_id' => 'required|exists:branches,id',
                'dest_branch_id' => 'required|exists:branches,id',
                'service_level' => 'required|string|in:standard,express,same_day,overnight',
                'sender_name' => 'required|string|max:255',
                'sender_phone' => 'required|string|max:50',
                'sender_address' => 'required|string|max:500',
                'recipient_name' => 'required|string|max:255',
                'recipient_phone' => 'required|string|max:50',
                'recipient_address' => 'required|string|max:500',
                'weight' => 'nullable|numeric|min:0',
                'pieces' => 'nullable|integer|min:1',
                'description' => 'nullable|string|max:500',
                'payment_method' => 'nullable|string|in:cash,prepaid,credit',
                'declared_value' => 'nullable|numeric|min:0',
                'price_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!$request->filled('client_id') && !$request->filled('customer_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either client_id or customer_id is required',
                    'errors' => [
                        'client_id' => ['The client_id field is required when customer_id is not present.'],
                        'customer_id' => ['The customer_id field is required when client_id is not present.'],
                    ],
                ], 422);
            }

            DB::beginTransaction();

            // Generate tracking number
            $trackingNumber = 'BRK-' . date('Ymd') . '-' . str_pad(Shipment::whereDate('created_at', today())->count() + 1, 5, '0', STR_PAD_LEFT);

            $shipmentData = [
                'client_id' => $request->input('client_id'),
                'customer_id' => $request->input('customer_id'),
                'origin_branch_id' => $request->input('origin_branch_id'),
                'dest_branch_id' => $request->input('dest_branch_id'),
                'tracking_number' => $trackingNumber,
                'service_level' => $request->input('service_level'),
                'status' => 'booked',
                'current_status' => ShipmentStatus::BOOKED->value,
                'booked_at' => now(),
                'price_amount' => $request->input('price_amount', 0),
                'currency' => 'UGX',
                'created_by' => $request->user()?->id,
                'priority' => $this->calculatePriority($request->input('service_level')),
                'metadata' => [
                    'sender' => [
                        'name' => $request->input('sender_name'),
                        'phone' => $request->input('sender_phone'),
                        'address' => $request->input('sender_address'),
                    ],
                    'recipient' => [
                        'name' => $request->input('recipient_name'),
                        'phone' => $request->input('recipient_phone'),
                        'address' => $request->input('recipient_address'),
                    ],
                    'package' => [
                        'weight' => $request->input('weight'),
                        'pieces' => $request->input('pieces', 1),
                        'description' => $request->input('description'),
                    ],
                    'payment' => [
                        'method' => $request->input('payment_method', 'cash'),
                        'declared_value' => $request->input('declared_value'),
                    ],
                ],
            ];

            $shipment = Shipment::create($shipmentData);

            DB::commit();

            $this->lifecycleService->transition($shipment->fresh(), ShipmentStatus::BOOKED, [
                'trigger' => 'api_v10.store',
                'performed_by' => $request->user()?->id,
                'timestamp' => $shipment->booked_at ?? now(),
                'force' => true,
                'location_type' => 'branch',
                'location_id' => $request->input('origin_branch_id'),
            ]);

            $shipment->load([
                'originBranch:id,name,code',
                'destBranch:id,name,code',
                'destinationBranch:id,name,code',
                'client:id,business_name',
                'customer:id,name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully',
                'data' => $shipment,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ShipmentsApiController@store: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate priority based on service level
     */
    private function calculatePriority(string $serviceLevel): int
    {
        return match ($serviceLevel) {
            'same_day' => 1,
            'express' => 2,
            'overnight' => 3,
            'standard' => 4,
            default => 5,
        };
    }

    /**
     * Get shipment statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $pendingStatuses = array_map(fn (ShipmentStatus $status) => $status->value, [
                ShipmentStatus::BOOKED,
                ShipmentStatus::PICKUP_SCHEDULED,
                ShipmentStatus::PICKED_UP,
                ShipmentStatus::AT_ORIGIN_HUB,
                ShipmentStatus::BAGGED,
            ]);

            $inTransitStatuses = array_map(fn (ShipmentStatus $status) => $status->value, [
                ShipmentStatus::LINEHAUL_DEPARTED,
                ShipmentStatus::LINEHAUL_ARRIVED,
                ShipmentStatus::AT_DESTINATION_HUB,
                ShipmentStatus::CUSTOMS_HOLD,
                ShipmentStatus::CUSTOMS_CLEARED,
                ShipmentStatus::OUT_FOR_DELIVERY,
            ]);

            $stats = [
                'total' => Shipment::count(),
                'pending' => Shipment::whereIn('current_status', $pendingStatuses)->count(),
                'in_transit' => Shipment::whereIn('current_status', $inTransitStatuses)->count(),
                'delivered' => Shipment::where('current_status', ShipmentStatus::DELIVERED->value)->count(),
                'exceptions' => Shipment::where('has_exception', true)->count(),
                'today' => Shipment::whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('ShipmentsApiController@statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve the columns that exist on the branch_workers table so eager loads do not fail
     */
    private function resolveAssignedWorkerColumns(): array
    {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $columns = ['id', 'user_id', 'branch_id', 'role'];

        if (Schema::hasColumn('branch_workers', 'employment_status')) {
            $columns[] = 'employment_status';
        }

        if (Schema::hasColumn('branch_workers', 'contact_phone')) {
            $columns[] = 'contact_phone';
        }

        return $columns;
    }
}
