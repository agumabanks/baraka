<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Models\Backend\Client;
use App\Models\Backend\Branch;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientsApiController extends Controller
{
    /**
     * Get paginated list of clients
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 50);
            $search = $request->input('search');
            $branchId = $request->input('branch_id');
            $status = $request->input('status', 'active');

            $query = Client::with(['primaryBranch:id,name,code'])
                ->orderBy('business_name', 'asc');

            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }

            // Filter by branch
            if ($branchId) {
                $query->where('primary_branch_id', $branchId);
            }

            // Search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('business_name', 'like', "%{$search}%")
                      ->orWhereRaw("JSON_EXTRACT(kyc_data, '$.contact_name') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_EXTRACT(kyc_data, '$.contact_phone') LIKE ?", ["%{$search}%"]);
                });
            }

            $clients = $query->paginate($perPage);

            // Add shipment counts
            $clientsWithCounts = $clients->map(function ($client) {
                $client->shipments_count = Shipment::where('client_id', $client->id)->count();
                $client->active_shipments_count = Shipment::where('client_id', $client->id)
                    ->whereNotIn('current_status', ['delivered', 'cancelled'])
                    ->count();
                return $client;
            });

            return response()->json([
                'success' => true,
                'data' => $clientsWithCounts,
                'pagination' => [
                    'total' => $clients->total(),
                    'per_page' => $clients->perPage(),
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@index: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get clients for a specific branch
     */
    public function getByBranch(Request $request, $branchId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 50);
            $search = $request->input('search');

            $branch = Branch::findOrFail($branchId);

            $query = $branch->primaryClients()
                ->with(['primaryBranch:id,name,code'])
                ->orderBy('business_name', 'asc');

            if ($search) {
                $query->where('business_name', 'like', "%{$search}%");
            }

            $clients = $query->paginate($perPage);

            // Add shipment counts
            $clientsWithCounts = $clients->map(function ($client) {
                $client->shipments_count = Shipment::where('client_id', $client->id)->count();
                $client->active_shipments_count = Shipment::where('client_id', $client->id)
                    ->whereNotIn('current_status', ['delivered', 'cancelled'])
                    ->count();
                return $client;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'branch' => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                    ],
                    'clients' => $clientsWithCounts,
                ],
                'pagination' => [
                    'total' => $clients->total(),
                    'per_page' => $clients->perPage(),
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@getByBranch: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client details
     */
    public function show($clientId): JsonResponse
    {
        try {
            $client = Client::with(['primaryBranch:id,name,code'])
                ->findOrFail($clientId);

            // Get shipment statistics
            $stats = [
                'total_shipments' => Shipment::where('client_id', $clientId)->count(),
                'active_shipments' => Shipment::where('client_id', $clientId)
                    ->whereNotIn('current_status', ['delivered', 'cancelled'])
                    ->count(),
                'delivered_shipments' => Shipment::where('client_id', $clientId)
                    ->where('current_status', 'delivered')
                    ->count(),
                'pending_shipments' => Shipment::where('client_id', $clientId)
                    ->where('current_status', 'pending_processing')
                    ->count(),
            ];

            // Get recent shipments
            $recentShipments = Shipment::where('client_id', $clientId)
                ->with(['originBranch:id,name,code', 'destinationBranch:id,name,code'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'client' => $client,
                    'statistics' => $stats,
                    'recent_shipments' => $recentShipments,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@show: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client details',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new client
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'primary_branch_id' => 'required|exists:branches,id',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $kycData = array_filter([
                'contact_name' => $request->input('contact_name'),
                'contact_phone' => $request->input('contact_phone'),
                'contact_email' => $request->input('contact_email'),
                'address' => $request->input('address'),
            ]);

            $client = Client::create([
                'business_name' => $request->input('business_name'),
                'primary_branch_id' => $request->input('primary_branch_id'),
                'status' => $request->input('status', 'active'),
                'kyc_data' => $kycData,
            ]);

            $client->load('primaryBranch:id,name,code');

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => $client,
            ], 201);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@store: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update client
     */
    public function update(Request $request, $clientId): JsonResponse
    {
        try {
            $client = Client::findOrFail($clientId);

            $validator = Validator::make($request->all(), [
                'business_name' => 'sometimes|required|string|max:255',
                'primary_branch_id' => 'sometimes|required|exists:branches,id',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateData = [];

            if ($request->has('business_name')) {
                $updateData['business_name'] = $request->input('business_name');
            }

            if ($request->has('primary_branch_id')) {
                $updateData['primary_branch_id'] = $request->input('primary_branch_id');
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->input('status');
            }

            // Update KYC data
            if ($request->hasAny(['contact_name', 'contact_phone', 'contact_email', 'address'])) {
                $kycData = $client->kyc_data ?? [];
                
                if ($request->has('contact_name')) $kycData['contact_name'] = $request->input('contact_name');
                if ($request->has('contact_phone')) $kycData['contact_phone'] = $request->input('contact_phone');
                if ($request->has('contact_email')) $kycData['contact_email'] = $request->input('contact_email');
                if ($request->has('address')) $kycData['address'] = $request->input('address');

                $updateData['kyc_data'] = $kycData;
            }

            $client->update($updateData);
            $client->load('primaryBranch:id,name,code');

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully',
                'data' => $client,
            ]);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@update: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete client
     */
    public function destroy($clientId): JsonResponse
    {
        try {
            $client = Client::findOrFail($clientId);

            // Check if client has shipments
            $shipmentsCount = Shipment::where('client_id', $clientId)->count();

            if ($shipmentsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete client with existing shipments. Please archive instead.',
                ], 400);
            }

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@destroy: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');

            $query = Client::query();

            if ($branchId) {
                $query->where('primary_branch_id', $branchId);
            }

            $stats = [
                'total_clients' => $query->count(),
                'active_clients' => (clone $query)->where('status', 'active')->count(),
                'inactive_clients' => (clone $query)->where('status', 'inactive')->count(),
                'clients_with_shipments' => (clone $query)->whereHas('shipments')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('ClientsApiController@statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
