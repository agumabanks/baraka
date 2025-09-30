<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreQuoteRequest;
use App\Http\Resources\Api\V1\QuoteResource;
use App\Models\Quotation;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Quotes",
 *     description="API Endpoints for quotation management"
 * )
 */
class QuotesController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/quotes",
     *     summary="Create quotation",
     *     description="Create a new shipment quotation with pricing",
     *     operationId="createQuote",
     *     tags={"Quotes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreQuoteRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Quotation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation created"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="quote", ref="#/components/schemas/Quotation")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreQuoteRequest $request)
    {
        // Calculate pricing based on existing domain logic
        $baseCharge = $this->calculateBaseCharge($request);
        $surcharges = $this->calculateSurcharges($request);
        $totalAmount = $baseCharge + array_sum($surcharges);

        $quotation = Quotation::create([
            'customer_id' => auth()->id(),
            'origin_branch_id' => $request->origin_branch_id,
            'destination_country' => $request->destination_country,
            'service_type' => $request->service_type,
            'pieces' => $request->pieces,
            'weight_kg' => $request->weight_kg,
            'volume_cm3' => $request->volume_cm3,
            'dim_factor' => $request->dim_factor,
            'base_charge' => $baseCharge,
            'surcharges_json' => $surcharges,
            'total_amount' => $totalAmount,
            'currency' => $request->currency,
            'status' => 'pending',
            'valid_until' => now()->addDays(7), // Valid for 7 days
            'created_by_id' => auth()->id(),
        ]);

        return $this->responseWithSuccess('Quotation created successfully', [
            'quote' => new QuoteResource($quotation),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/quotes/{quote}",
     *     summary="Get quotation details",
     *     description="Retrieve detailed quotation information",
     *     operationId="getQuote",
     *     tags={"Quotes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="quote",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="quote", ref="#/components/schemas/Quotation")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not owner"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quotation not found"
     *     )
     * )
     */
    public function show(Quotation $quote)
    {
        // Check if user owns this quotation
        if ($quote->customer_id !== auth()->id()) {
            return $this->responseWithError('Not authorized to view this quotation', [], 403);
        }

        return $this->responseWithSuccess('Quotation retrieved successfully', [
            'quote' => new QuoteResource($quote),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/quotes",
     *     summary="List user quotations",
     *     description="Retrieve paginated list of user's quotations",
     *     operationId="getUserQuotes",
     *     tags={"Quotes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by quotation status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="quotes", type="array", @OA\Items(ref="#/components/schemas/Quotation"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Quotation::where('customer_id', auth()->id());

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $quotes = $query->orderBy('created_at', 'desc')
                       ->paginate(20);

        return $this->responseWithSuccess('Quotations retrieved successfully', [
            'quotes' => QuoteResource::collection($quotes),
        ]);
    }

    /**
     * Calculate base charge for shipment.
     * This would integrate with existing pricing logic.
     */
    private function calculateBaseCharge(StoreQuoteRequest $request): float
    {
        // TODO: Integrate with existing pricing/rate card logic
        // For now, return a base calculation
        $weight = $request->weight_kg;
        $volume = $request->volume_cm3 ? $request->volume_cm3 / 5000 : 0; // Convert to kg
        $chargeableWeight = max($weight, $volume);

        return $chargeableWeight * 10; // $10 per kg base rate
    }

    /**
     * Calculate surcharges for shipment.
     * This would integrate with existing surcharge rules.
     */
    private function calculateSurcharges(StoreQuoteRequest $request): array
    {
        $surcharges = [];

        // TODO: Integrate with existing surcharge rules
        // Fuel surcharge
        $surcharges['fuel'] = $this->calculateBaseCharge($request) * 0.15;

        // Remote area surcharge (if applicable)
        if ($request->is_remote_area) {
            $surcharges['remote'] = 25.00;
        }

        return $surcharges;
    }
}