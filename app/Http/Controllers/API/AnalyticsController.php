<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Cache\AnalyticsCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AnalyticsController extends Controller
{
    protected $cacheService;

    public function __construct(AnalyticsCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/dashboard/{branchId}",
     *     summary="Get dashboard metrics for a branch",
     *     tags={"Analytics"},
     *     @OA\Parameter(
     *         name="branchId",
     *         in="path",
     *         required=true,
     *         description="Branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="dateRange",
     *         in="query",
     *         description="Date range filter (YYYY-MM-DD,YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2023-01-01,2023-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard metrics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Dashboard metrics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid parameters"),
     *     @OA\Response(response=404, description="Branch not found")
     * )
     */
    public function getDashboardMetrics(Request $request, int $branchId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dateRange' => 'sometimes|string|regex:/^\d{4}-\d{2}-\d{2},\d{4}-\d{2}-\d{2}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format. Use YYYY-MM-DD,YYYY-MM-DD',
                    'errors' => $validator->errors()
                ], 400);
            }

            $dateRange = null;
            if ($request->has('dateRange')) {
                $dates = explode(',', $request->dateRange);
                $dateRange = [$dates[0], $dates[1]];
            }

            $metrics = $this->cacheService->getDashboardMetrics($branchId, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'cached' => true,
                'message' => 'Dashboard metrics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/operational/{branchId}",
     *     summary="Get operational reports for a branch",
     *     tags={"Analytics"},
     *     @OA\Parameter(
     *         name="branchId",
     *         in="path",
     *         required=true,
     *         description="Branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="reportType",
     *         in="query",
     *         required=true,
     *         description="Type of operational report",
     *         @OA\Schema(
     *             type="string",
     *             enum={"daily_summary", "weekly_summary", "monthly_summary", "performance_metrics"},
     *             example="daily_summary"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="dateRange",
     *         in="query",
     *         description="Date range filter (YYYY-MM-DD,YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2023-01-01,2023-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operational report retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="message", type="string", example="Operational report retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getOperationalReport(Request $request, int $branchId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reportType' => 'required|in:daily_summary,weekly_summary,monthly_summary,performance_metrics',
                'dateRange' => 'sometimes|string|regex:/^\d{4}-\d{2}-\d{2},\d{4}-\d{2}-\d{2}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            $dateRange = null;
            if ($request->has('dateRange')) {
                $dates = explode(',', $request->dateRange);
                $dateRange = [$dates[0], $dates[1]];
            }

            $report = $this->cacheService->getOperationalReport(
                $branchId, 
                $request->reportType, 
                $dateRange
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'cached' => true,
                'message' => 'Operational report retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving operational report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/financial/{clientId}",
     *     summary="Get financial reports for a client",
     *     tags={"Analytics"},
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="dateRange",
     *         in="query",
     *         description="Date range filter (YYYY-MM-DD,YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2023-01-01,2023-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="reportType",
     *         in="query",
     *         description="Type of financial report",
     *         @OA\Schema(
     *             type="string",
     *             enum={"revenue_summary", "expense_breakdown", "profit_loss", "cash_flow"},
     *             example="revenue_summary"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Financial report retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="message", type="string", example="Financial report retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getFinancialReport(Request $request, int $clientId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dateRange' => 'sometimes|string|regex:/^\d{4}-\d{2}-\d{2},\d{4}-\d{2}-\d{2}$/',
                'reportType' => 'sometimes|in:revenue_summary,expense_breakdown,profit_loss,cash_flow'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            $dateRange = null;
            if ($request->has('dateRange')) {
                $dates = explode(',', $request->dateRange);
                $dateRange = [$dates[0], $dates[1]];
            }

            $report = $this->cacheService->getFinancialReport($clientId, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $report,
                'cached' => true,
                'message' => 'Financial report retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving financial report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/performance/{branchId}",
     *     summary="Get performance metrics for a branch",
     *     tags={"Analytics"},
     *     @OA\Parameter(
     *         name="branchId",
     *         in="path",
     *         required=true,
     *         description="Branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="dateRange",
     *         in="query",
     *         description="Date range filter (YYYY-MM-DD,YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2023-01-01,2023-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Performance metrics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="message", type="string", example="Performance metrics retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getPerformanceMetrics(Request $request, int $branchId): JsonResponse
    {
        try {
            $dateRange = null;
            if ($request->has('dateRange')) {
                $dates = explode(',', $request->dateRange);
                $dateRange = [$dates[0], $dates[1]];
            }

            $metrics = $this->cacheService->getPerformanceMetrics($branchId, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'cached' => true,
                'message' => 'Performance metrics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/analytics/cache/preload",
     *     summary="Preload cache for specific data",
     *     tags={"Analytics"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="branchIds", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="dateRange", type="array", minItems=2, maxItems=2, 
     *                 @OA\Items(type="string", format="date"),
     *                 example={"2023-01-01", "2023-12-31"}
     *             ),
     *             @OA\Property(property="clientIds", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cache preloading initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cache preloading initiated"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function preloadCache(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'branchIds' => 'sometimes|array',
                'branchIds.*' => 'integer|exists:dim_branch,branch_id',
                'dateRange' => 'sometimes|array|size:2',
                'dateRange.*' => 'date',
                'clientIds' => 'sometimes|array',
                'clientIds.*' => 'integer|exists:dim_client,client_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            $result = [];
            
            if ($request->has('branchIds')) {
                foreach ($request->branchIds as $branchId) {
                    $this->cacheService->preloadCommonData($branchId);
                    $result['branch_' . $branchId] = 'preloaded';
                }
            }
            
            if ($request->has('dateRange')) {
                $this->cacheService->warmUpCacheForDateRange(
                    $request->dateRange,
                    $request->branchIds ?? []
                );
                $result['date_range'] = 'warmed up';
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Cache preloading initiated'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error preloading cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/analytics/cache/clear",
     *     summary="Clear cache by patterns or tags",
     *     tags={"Analytics"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patterns", type="array", @OA\Items(type="string"),
     *                 example={"dashboard:*", "operational:*", "financial:*"}
     *             ),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"),
     *                 example={"branch:123", "client:456"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cache cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cache cleared successfully")
     *         )
     *     )
     * )
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patterns' => 'sometimes|array',
                'patterns.*' => 'string|min:1',
                'tags' => 'sometimes|array',
                'tags.*' => 'string|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            if ($request->has('patterns')) {
                foreach ($request->patterns as $pattern) {
                    $this->cacheService->invalidatePattern($pattern);
                }
            }

            if ($request->has('tags')) {
                $this->cacheService->invalidateByTags($request->tags);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/cache/stats",
     *     summary="Get cache statistics",
     *     tags={"Analytics"},
     *     @OA\Response(
     *         response=200,
     *         description="Cache statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Cache statistics retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getCacheStats(): JsonResponse
    {
        try {
            $stats = $this->cacheService->getCacheStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Cache statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving cache statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}