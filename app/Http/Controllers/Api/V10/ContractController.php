<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\ContractResource;
use App\Models\Contract;
use App\Services\ContractManagementService;
use App\Services\ContractComplianceService;
use App\Services\VolumeDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Contract Management API Controller
 * 
 * Handles all contract-related API endpoints including:
 * - Contract CRUD operations
 * - Compliance monitoring
 * - Volume discount calculations
 * - Contract lifecycle management
 */
class ContractController extends Controller
{
    public function __construct(
        private ContractManagementService $contractService,
        private ContractComplianceService $complianceService,
        private VolumeDiscountService $volumeService
    ) {}

    /**
     * Get all contracts with filtering and pagination
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => 'sometimes|in:active,inactive,draft,expired,suspended,all',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:10|max:100'
        ]);

        $query = Contract::with(['customer', 'template']);

        // Apply filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        $contracts = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 20));

        return ContractResource::collection($contracts);
    }

    /**
     * Create a new contract
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'contract_type' => 'sometimes|string|in:standard,premium,enterprise',
        ]);

        try {
            $contractData = $request->only([
                'customer_id', 'name', 'start_date', 'end_date', 'contract_type'
            ]);
            $contractData['status'] = 'draft';
            
            $contract = $this->contractService->createContract($contractData);

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => new ContractResource($contract)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get a specific contract
     */
    public function show(Contract $contract): JsonResponse
    {
        $contract->load(['customer', 'template', 'volumeDiscounts']);

        return response()->json([
            'success' => true,
            'data' => new ContractResource($contract)
        ]);
    }

    /**
     * Update a contract
     */
    public function update(Request $request, Contract $contract): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'end_date' => 'sometimes|date|after:contract.end_date',
        ]);

        try {
            $updateData = $request->only(['name', 'end_date']);
            $updatedContract = $this->contractService->updateContract($contract->id, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully',
                'data' => new ContractResource($updatedContract)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Activate a contract
     */
    public function activate(Contract $contract): JsonResponse
    {
        try {
            $result = $this->contractService->activateContract($contract->id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Contract activated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate contract: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get contract compliance status
     */
    public function compliance(Contract $contract): JsonResponse
    {
        try {
            $complianceStatus = $this->complianceService->getContractComplianceStatus($contract->id);

            return response()->json([
                'success' => true,
                'data' => $complianceStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get compliance status: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get applicable discounts for contract
     */
    public function discounts(Request $request, Contract $contract): JsonResponse
    {
        $request->validate([
            'volume' => 'required|integer|min:1',
        ]);

        try {
            $discounts = $this->volumeService->calculateDiscountsForVolume($contract, $request->volume);

            return response()->json([
                'success' => true,
                'data' => $discounts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate discounts: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate contract summary report
     */
    public function summary(Contract $contract): JsonResponse
    {
        try {
            $summary = $contract->getContractSummary();

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary: ' . $e->getMessage()
            ], 400);
        }
    }
}