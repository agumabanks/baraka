<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Global search endpoint
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'branch_id' => 'nullable|integer|exists:hubs,id',
            'status' => 'nullable|string',
            'type' => 'nullable|in:shipment,parcel,customer',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        try {
            $filters = [
                'branch_id' => $request->branch_id,
                'status' => $request->status,
                'type' => $request->type,
                'per_page' => $request->per_page ?? 20,
            ];

            $results = $this->searchService->search($request->q, $filters, auth()->user());

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'meta' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                ],
                'query' => $request->q,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quick search for autocomplete
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
            'limit' => 'nullable|integer|min:5|max:20',
        ]);

        try {
            $limit = $request->limit ?? 10;
            $results = $this->searchService->search($request->q, ['per_page' => $limit], auth()->user());

            $suggestions = collect($results->items())->map(function ($item) {
                return [
                    'id' => $item['model']->id,
                    'type' => $item['type'],
                    'text' => $item['title'],
                    'subtitle' => $item['subtitle'],
                    'url' => $item['url'],
                ];
            });

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
                'query' => $request->q,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Autocomplete failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Advanced search with filters
     */
    public function advanced(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'nullable|string|min:2|max:100',
            'type' => 'nullable|in:shipment,parcel,customer',
            'status' => 'nullable|string',
            'branch_id' => 'nullable|integer|exists:hubs,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'customer_id' => 'nullable|integer|exists:users,id',
            'per_page' => 'nullable|integer|min:10|max:100',
            'sort_by' => 'nullable|in:created_at,updated_at,relevance',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        try {
            $filters = array_filter([
                'branch_id' => $request->branch_id,
                'status' => $request->status,
                'type' => $request->type,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'customer_id' => $request->customer_id,
                'per_page' => $request->per_page ?? 20,
                'sort_by' => $request->sort_by ?? 'relevance',
                'sort_order' => $request->sort_order ?? 'desc',
            ]);

            $query = $request->query ?? '';
            $results = $this->searchService->search($query, $filters, auth()->user());

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'meta' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'filters' => $filters,
                ],
                'query' => $query,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Advanced search failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // This would typically aggregate search analytics
            // For now, return basic counts
            $stats = [
                'total_shipments' => \App\Models\Shipment::count(),
                'total_parcels' => \App\Models\Backend\Parcel::count(),
                'total_customers' => \App\Models\User::clients()->count(),
                'recent_searches' => [], // Would be populated from search logs
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get search stats: '.$e->getMessage(),
            ], 500);
        }
    }
}
