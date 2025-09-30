<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\User;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin Customers",
 *     description="API Endpoints for admin customer management"
 * )
 */
class CustomerController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/admin/customers",
     *     summary="List customers",
     *     description="Retrieve paginated list of customers with optional search and filters",
     *     operationId="getAdminCustomers",
     *     tags={"Admin Customers"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name, email, or phone",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_type",
     *         in="query",
     *         description="Filter by user type (merchant, customer)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customers retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customers retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customers", type="array", @OA\Items(ref="#/components/schemas/Customer"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('mobile', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->user_type) {
            $query->where('user_type', $request->user_type);
        }

        $customers = $query->with(['shipments', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->responseWithSuccess('Customers retrieved', [
            'customers' => CustomerResource::collection($customers),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/customers/{id}",
     *     summary="Get customer details",
     *     description="Retrieve detailed information about a specific customer",
     *     operationId="getAdminCustomer",
     *     tags={"Admin Customers"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function show(User $customer)
    {
        $customer->load(['shipments', 'merchant', 'devices']);

        return $this->responseWithSuccess('Customer retrieved', [
            'customer' => new CustomerResource($customer),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/customers/{id}",
     *     summary="Update customer",
     *     description="Update customer information and settings",
     *     operationId="updateAdminCustomer",
     *     tags={"Admin Customers"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdateCustomerRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdateCustomerRequest $request, User $customer)
    {
        $customer->update($request->validated());

        return $this->responseWithSuccess('Customer updated', [
            'customer' => new CustomerResource($customer->fresh()),
        ]);
    }
}
