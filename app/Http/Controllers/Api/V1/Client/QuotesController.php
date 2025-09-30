<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreQuoteRequest;
use App\Http\Resources\Api\V1\QuoteResource;
use App\Models\Quotation;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Quotes",
 *     description="API Endpoints for quote management"
 * )
 */
class QuotesController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/quotes",
     *     summary="List user quotes",
     *     description="Retrieve paginated list of user's quotes",
     *     operationId="getQuotes",
     *     tags={"Quotes"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Quotes retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotes retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="quotes", type="array", @OA\Items(ref="#/components/schemas/Quote"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $quotes = Quotation::where('customer_id', auth()->id())
            ->paginate($request->per_page ?? 15);

        return $this->responseWithSuccess('Quotes retrieved', [
            'quotes' => QuoteResource::collection($quotes),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/quotes",
     *     summary="Create new quote",
     *     description="Create a new quote request",
     *     operationId="createQuote",
     *     tags={"Quotes"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StoreQuoteRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Quote created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quote created"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="quote", ref="#/components/schemas/Quote")
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreQuoteRequest $request)
    {
        $quote = Quotation::create([
            'customer_id' => auth()->id(),
            ...$request->validated(),
        ]);

        return $this->responseWithSuccess('Quote created', [
            'quote' => new QuoteResource($quote),
        ], 201);
    }
}
