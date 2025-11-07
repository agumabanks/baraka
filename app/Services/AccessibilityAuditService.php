<?php

namespace App\Services;

use App\Models\AccessibilityComplianceLog;
use App\Models\AccessibilityTestQueue;
use App\Models\ComplianceViolation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccessibilityAuditService
{
    /**
     * Run automated WCAG compliance test on a page
     */
    public function runAccessibilityTest(string $pageUrl, string $testType = 'automated', array $config = []): AccessibilityComplianceLog
    {
        $testId = $this->generateTestId();
        
        // Add test to queue for processing
        $testQueue = AccessibilityTestQueue::create([
            'job_id' => $testId,
            'page_url' => $pageUrl,
            'test_type' => $testType,
            'test_config' => $config,
            'status' => 'pending',
            'scheduled_at' => now(),
            'priority' => 1,
        ]);

        // For automated tests, we can run immediately
        if ($testType === 'automated') {
            $this->processTestQueue($testQueue);
        }

        return $testQueue;
    }

    /**
     * Process accessibility test queue
     */
    public function processTestQueue(AccessibilityTestQueue $queueItem): void
    {
        $queueItem->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $testResults = $this->runAxeTest($queueItem->page_url, $queueItem->test_config);
            
            $complianceLog = AccessibilityComplianceLog::create([
                'test_id' => $queueItem->job_id,
                'page_url' => $queueItem->page_url,
                'test_type' => $queueItem->test_type,
                'wcag_version' => ['2.1', 'AA'],
                'test_results' => $testResults,
                'compliance_score' => $this->calculateComplianceScore($testResults),
                'violations' => $this->filterIssues($testResults, 'violations'),
                'warnings' => $this->filterIssues($testResults, 'warnings'),
                'passes' => $this->filterIssues($testResults, 'passes'),
                'tested_by' => 'automated_system',
                'tested_at' => now(),
                'metadata' => array_merge($queueItem->test_config, [
                    'test_duration_ms' => $this->calculateTestDuration($queueItem),
                    'user_agent' => $queueItem->metadata['user_agent'] ?? 'Automated Test Runner',
                ]),
            ]);

            // Create compliance violations for critical issues
            $this->createComplianceViolations($complianceLog);

            $queueItem->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Accessibility test failed', [
                'test_id' => $queueItem->job_id,
                'error' => $e->getMessage(),
            ]);

            $queueItem->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Run axe-core accessibility test
     */
    private function runAxeTest(string $pageUrl, array $config = []): array
    {
        // This would integrate with a headless browser and axe-core
        // For now, we'll simulate the test results
        $simulatedResults = [
            'violations' => [
                [
                    'id' => 'color-contrast',
                    'impact' => 'serious',
                    'description' => 'Elements must have sufficient color contrast',
                    'help' => 'Ensure color contrast of at least 4.5:1',
                    'helpUrl' => 'https://dequeuniversity.com/rules/axe/4.4/color-contrast',
                    'tags' => ['wcag2aa', 'wcag143', 'color-contrast'],
                    'nodes' => [
                        [
                            'html' => '<button class="btn-primary">Submit</button>',
                            'target' => ['.btn-primary'],
                            'failureSummary' => 'Element has insufficient color contrast of 3.2:1 (foreground color: #666666, background color: #ffffff)',
                        ]
                    ]
                ],
                [
                    'id' => 'image-alt',
                    'impact' => 'critical',
                    'description' => 'Images must have alternative text',
                    'help' => 'All images must have an alt attribute',
                    'helpUrl' => 'https://dequeuniversity.com/rules/axe/4.4/image-alt',
                    'tags' => ['wcag2a', 'wcag111', 'image-alt'],
                    'nodes' => [
                        [
                            'html' => '<img src="chart.png">',
                            'target' => ['img[src="chart.png"]'],
                            'failureSummary' => 'Element does not have an alt attribute',
                        ]
                    ]
                ]
            ],
            'passes' => [
                [
                    'id' => 'button-name',
                    'description' => 'Buttons must have discernible text',
                    'help' => 'Ensure all buttons have accessible names',
                    'helpUrl' => 'https://dequeuniversity.com/rules/axe/4.4/button-name',
                ]
            ],
            'incomplete' => [
                [
                    'id' => 'aria-allowed-attr',
                    'description' => 'ARIA attributes must be valid',
                    'help' => 'ARIA attributes must conform to valid values',
                ]
            ]
        ];

        return $simulatedResults;
    }

    /**
     * Calculate overall compliance score (0-100)
     */
    private function calculateComplianceScore(array $testResults): float
    {
        $totalChecks = count($testResults['violations']) + count($testResults['passes']) + count($testResults['incomplete']);
        
        if ($totalChecks === 0) {
            return 100.0;
        }

        $passedChecks = count($testResults['passes']);
        $partialChecks = count($testResults['incomplete']);
        
        // Weight the score: passes = 100%, incomplete = 50%, violations = 0%
        $score = (($passedChecks * 1.0) + ($partialChecks * 0.5)) / $totalChecks * 100;
        
        return round($score, 2);
    }

    /**
     * Filter issues by type (violations, warnings, passes)
     */
    private function filterIssues(array $results, string $type): array
    {
        return $results[$type] ?? [];
    }

    /**
     * Create compliance violations for critical accessibility issues
     */
    private function createComplianceViolations(AccessibilityComplianceLog $complianceLog): void
    {
        foreach ($complianceLog->violations as $violation) {
            if (in_array($violation['impact'] ?? '', ['critical', 'serious'])) {
                ComplianceViolation::create([
                    'violation_id' => $this->generateViolationId(),
                    'compliance_framework' => 'WCAG',
                    'violation_type' => $violation['id'],
                    'severity' => $violation['impact'],
                    'description' => $violation['description'],
                    'affected_records' => array_map(fn($node) => $node['html'], $violation['nodes'] ?? []),
                    'discovered_by' => 'automated_scan',
                    'discovered_by_user_id' => null,
                    'discovered_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get accessibility compliance summary for a page
     */
    public function getComplianceSummary(string $pageUrl): array
    {
        $latestTest = AccessibilityComplianceLog::where('page_url', $pageUrl)
                                                ->latest('tested_at')
                                                ->first();

        if (!$latestTest) {
            return [
                'status' => 'not_tested',
                'score' => 0,
                'violations' => [],
                'warnings' => [],
                'last_tested' => null,
            ];
        }

        return [
            'status' => $latestTest->compliance_status,
            'score' => $latestTest->compliance_score,
            'violations' => $latestTest->violations,
            'warnings' => $latestTest->warnings,
            'last_tested' => $latestTest->tested_at,
            'wcag_level' => $latestTest->wcag_level,
            'critical_violations' => $latestTest->critical_violations_count,
            'total_issues' => $latestTest->total_issues,
        ];
    }

    /**
     * Run batch accessibility tests for multiple URLs
     */
    public function runBatchAccessibilityTest(array $urls, string $testType = 'automated'): array
    {
        $results = [];
        
        foreach ($urls as $url) {
            $results[$url] = $this->runAccessibilityTest($url, $testType);
        }

        return $results;
    }

    /**
     * Get accessibility trends over time
     */
    public function getAccessibilityTrends(string $pageUrl, int $days = 30): array
    {
        $tests = AccessibilityComplianceLog::where('page_url', $pageUrl)
                                          ->where('tested_at', '>=', now()->subDays($days))
                                          ->orderBy('tested_at')
                                          ->get();

        return [
            'scores_over_time' => $tests->mapWithKeys(function ($test) {
                return [$test->tested_at->format('Y-m-d') => $test->compliance_score];
            }),
            'violations_trend' => $tests->mapWithKeys(function ($test) {
                return [$test->tested_at->format('Y-m-d') => count($test->violations)];
            }),
            'average_score' => $tests->avg('compliance_score'),
            'improvement_rate' => $this->calculateImprovementRate($tests),
        ];
    }

    /**
     * Schedule recurring accessibility tests
     */
    public function scheduleRecurringTests(string $pageUrl, string $frequency = 'daily'): void
    {
        $scheduledAt = match ($frequency) {
            'hourly' => now()->addHour(),
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay(),
        };

        AccessibilityTestQueue::create([
            'job_id' => $this->generateTestId(),
            'page_url' => $pageUrl,
            'test_type' => 'automated',
            'test_config' => ['recurring' => true, 'frequency' => $frequency],
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'priority' => 0, // Lower priority for recurring tests
            'metadata' => ['is_recurring' => true],
        ]);
    }

    /**
     * Calculate test duration
     */
    private function calculateTestDuration(AccessibilityTestQueue $queueItem): int
    {
        if (!$queueItem->started_at) {
            return 0;
        }

        $endTime = $queueItem->completed_at ?? now();
        return $endTime->diffInMilliseconds($queueItem->started_at);
    }

    /**
     * Calculate improvement rate over time
     */
    private function calculateImprovementRate($tests): float
    {
        if ($tests->count() < 2) {
            return 0.0;
        }

        $firstScore = $tests->first()->compliance_score;
        $lastScore = $tests->last()->compliance_score;

        if ($firstScore === 0) {
            return 0.0;
        }

        return round((($lastScore - $firstScore) / $firstScore) * 100, 2);
    }

    /**
     * Generate unique test ID
     */
    private function generateTestId(): string
    {
        return 'a11y_test_' . now()->format('Y-m-d_H-i-s') . '_' . Str::random(6);
    }

    /**
     * Generate unique violation ID
     */
    private function generateViolationId(): string
    {
        return 'violation_' . now()->format('Y-m-d_H-i-s') . '_' . Str::random(6);
    }
}