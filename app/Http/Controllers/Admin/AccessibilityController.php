<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AccessibilityAuditService;
use App\Services\AuditService;
use App\Models\AccessibilityComplianceLog;
use App\Models\ComplianceViolation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccessibilityController extends Controller
{
    protected AccessibilityAuditService $accessibilityService;
    protected AuditService $auditService;

    public function __construct(
        AccessibilityAuditService $accessibilityService,
        AuditService $auditService
    ) {
        $this->accessibilityService = $accessibilityService;
        $this->auditService = $auditService;
    }

    /**
     * Run accessibility test on a specific URL
     */
    public function runTest(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'test_type' => 'sometimes|in:automated,manual,user_testing',
            'config' => 'sometimes|array',
        ]);

        $testType = $request->input('test_type', 'automated');
        $config = $request->input('config', []);

        try {
            $complianceLog = $this->accessibilityService->runAccessibilityTest(
                $request->input('url'),
                $testType,
                $config
            );

            // Log the test action
            $this->auditService->logAction(
                actionType: 'accessibility_test',
                resourceType: 'accessibility_test',
                resourceId: $complianceLog->test_id,
                metadata: [
                    'test_url' => $request->input('url'),
                    'test_type' => $testType,
                    'compliance_score' => $complianceLog->compliance_score,
                ],
                severity: $complianceLog->compliance_score < 70 ? 'warning' : 'info'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'test_id' => $complianceLog->test_id,
                    'status' => 'completed',
                    'compliance_score' => $complianceLog->compliance_score,
                    'violations' => $complianceLog->violations,
                    'warnings' => $complianceLog->warnings,
                    'passes' => $complianceLog->passes,
                ],
                'message' => 'Accessibility test completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to run accessibility test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accessibility compliance summary for a URL
     */
    public function getComplianceSummary(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $summary = $this->accessibilityService->getComplianceSummary($request->input('url'));

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get accessibility trends for a URL
     */
    public function getAccessibilityTrends(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $days = $request->input('days', 30);
        $url = $request->input('url');

        $trends = $this->accessibilityService->getAccessibilityTrends($url, $days);

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Schedule recurring accessibility tests
     */
    public function scheduleRecurringTest(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
        ]);

        try {
            $this->accessibilityService->scheduleRecurringTests(
                $request->input('url'),
                $request->input('frequency')
            );

            // Log the scheduling action
            $this->auditService->logAction(
                actionType: 'create',
                resourceType: 'accessibility_schedule',
                metadata: [
                    'url' => $request->input('url'),
                    'frequency' => $request->input('frequency'),
                    'scheduled_by' => Auth::id(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Recurring accessibility test scheduled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to schedule accessibility test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all accessibility test results with filtering
     */
    public function getTestResults(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        $page = $request->input('page', 1);
        $url = $request->input('url');
        $minScore = $request->input('min_score');
        $testType = $request->input('test_type');

        $query = AccessibilityComplianceLog::query();

        if ($url) {
            $query->where('page_url', 'like', "%{$url}%");
        }

        if ($minScore !== null) {
            $query->where('compliance_score', '>=', $minScore);
        }

        if ($testType) {
            $query->where('test_type', $testType);
        }

        $results = $query->orderBy('tested_at', 'desc')
                        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ]
        ]);
    }

    /**
     * Get accessibility compliance violations
     */
    public function getComplianceViolations(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        $severity = $request->input('severity');
        $framework = $request->input('framework');
        $status = $request->input('status', 'open');

        $query = ComplianceViolation::query();

        if ($severity) {
            $query->where('severity', $severity);
        }

        if ($framework) {
            $query->where('compliance_framework', $framework);
        }

        if ($status === 'open') {
            $query->whereNull('resolved_at')->where('is_false_positive', false);
        } elseif ($status === 'resolved') {
            $query->whereNotNull('resolved_at');
        } elseif ($status === 'false_positive') {
            $query->where('is_false_positive', true);
        }

        $violations = $query->orderBy('discovered_at', 'desc')
                           ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $violations->items(),
            'pagination' => [
                'current_page' => $violations->currentPage(),
                'per_page' => $violations->perPage(),
                'total' => $violations->total(),
                'last_page' => $violations->lastPage(),
            ]
        ]);
    }

    /**
     * Mark compliance violation as resolved
     */
    public function resolveViolation(Request $request, string $violationId): JsonResponse
    {
        $request->validate([
            'resolution_notes' => 'sometimes|string',
            'is_false_positive' => 'sometimes|boolean',
        ]);

        try {
            $violation = ComplianceViolation::findOrFail($violationId);

            $updateData = [
                'resolved_by_user_id' => Auth::id(),
                'resolved_at' => now(),
            ];

            if ($request->has('resolution_notes')) {
                $updateData['resolution_notes'] = $request->input('resolution_notes');
            }

            if ($request->has('is_false_positive')) {
                $updateData['is_false_positive'] = $request->input('is_false_positive');
            }

            $violation->update($updateData);

            // Log the resolution action
            $this->auditService->logAction(
                actionType: 'update',
                resourceType: 'compliance_violation',
                resourceId: $violationId,
                metadata: [
                    'resolution_action' => 'resolved',
                    'resolution_notes' => $request->input('resolution_notes'),
                    'is_false_positive' => $request->input('is_false_positive', false),
                    'resolved_by' => Auth::id(),
                ],
                severity: 'info'
            );

            return response()->json([
                'success' => true,
                'message' => 'Violation marked as resolved successfully',
                'data' => $violation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to resolve violation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accessibility compliance overview
     */
    public function getComplianceOverview(Request $request): JsonResponse
    {
        $dateRange = $request->input('date_range', '7d');
        
        $days = match ($dateRange) {
            '1d' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $recentTests = AccessibilityComplianceLog::where('tested_at', '>=', now()->subDays($days))->get();
        
        $summary = [
            'total_tests' => $recentTests->count(),
            'average_score' => round($recentTests->avg('compliance_score'), 2),
            'critical_violations' => $recentTests->flatMap->violations
                ->filter(fn($v) => ($v['impact'] ?? '') === 'critical')
                ->count(),
            'non_compliant_pages' => $recentTests->where('compliance_score', '<', 70)->count(),
            'violations_by_type' => $recentTests->flatMap->violations
                ->groupBy('id')
                ->map->count(),
            'score_trends' => $recentTests->groupBy(fn($test) => $test->tested_at->format('Y-m-d'))
                ->map(fn($tests) => round($tests->avg('compliance_score'), 2)),
            'top_violations' => $recentTests->flatMap->violations
                ->groupBy('id')
                ->sortByDesc(fn($group) => $group->count())
                ->take(5)
                ->map->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}