<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\BranchStatus;
use App\Enums\BranchType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\Branch\StoreBranchRequest;
use App\Http\Requests\Api\V10\Branch\UpdateBranchRequest;
use App\Models\Backend\Branch;
use App\Services\BranchPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchManagementController extends Controller
{
    public function __construct(protected BranchPerformanceService $performanceService) {}

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $data = $this->prepareAttributes($request->validated());

        $branch = Branch::create($data);

        $this->performanceService->generateSnapshot($branch, 'daily');

        return response()->json([
            'success' => true,
            'data' => $branch->fresh()->load(['parent', 'children']),
            'message' => 'Branch created successfully',
        ], 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $data = $this->prepareAttributes($request->validated(), $branch);

        $branch->fill($data);
        $branch->save();

        $this->performanceService->generateSnapshot($branch->fresh(), 'daily', now(), false);

        return response()->json([
            'success' => true,
            'data' => $branch->fresh()->load(['parent', 'children']),
            'message' => 'Branch updated successfully',
        ]);
    }

    public function toggleStatus(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('toggleStatus', $branch);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE,MAINTENANCE,SUSPENDED'],
        ]);

        $status = BranchStatus::fromString($validated['status']);
        $branch->update(['status' => $status->toLegacy()]);

        return response()->json([
            'success' => true,
            'data' => $branch->fresh(),
            'message' => 'Branch status updated',
        ]);
    }

    protected function prepareAttributes(array $input, ?Branch $branch = null): array
    {
        if (isset($input['type'])) {
            $type = BranchType::fromString($input['type']);
            $input['type'] = $type->value;
            $input['is_hub'] = $type === BranchType::HUB;
        }

        if (array_key_exists('status', $input)) {
            $status = BranchStatus::fromString($input['status']);
            $input['status'] = $status->toLegacy();
        }

        if (isset($input['geo_lat'])) {
            $input['latitude'] = $input['geo_lat'];
        }

        if (isset($input['geo_lng'])) {
            $input['longitude'] = $input['geo_lng'];
        }

        if (isset($input['parent_branch_id']) && $branch && $branch->id === (int) $input['parent_branch_id']) {
            unset($input['parent_branch_id']);
        }

        return $input;
    }
}
