<?php

namespace App\Http\Middleware;

use App\Services\AccessibilityAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AccessibilityValidationMiddleware
{
    protected AccessibilityAuditService $accessibilityService;

    public function __construct(AccessibilityAuditService $accessibilityService)
    {
        $this->accessibilityService = $accessibilityService;
    }

    /**
     * Handle an incoming request for accessibility validation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a page that should be accessibility tested
        if ($this->shouldTestAccessibility($request)) {
            // Run accessibility test in background (non-blocking)
            $this->queueAccessibilityTest($request);
        }

        // Add accessibility headers to response
        $response = $next($request);
        
        // Add accessibility meta information to HTML responses
        if ($this->isHtmlResponse($response)) {
            $response = $this->addAccessibilityHeaders($response);
        }

        return $response;
    }

    /**
     * Determine if request should have accessibility testing
     */
    private function shouldTestAccessibility(Request $request): bool
    {
        // Only test GET requests for pages
        if (!$request->isMethod('GET')) {
            return false;
        }

        // Don't test API endpoints
        if (str_contains($request->path(), '/api/')) {
            return false;
        }

        // Test main pages and user-facing routes
        $testablePaths = [
            '/',
            '/dashboard',
            '/admin',
            '/pricing',
            '/contracts',
            '/reports',
            '/settings',
            '/profile',
        ];

        return collect($testablePaths)->some(function ($path) use ($request) {
            return str_starts_with($request->path(), $path) || $request->path() === $path;
        });
    }

    /**
     * Queue accessibility test for background processing
     */
    private function queueAccessibilityTest(Request $request): void
    {
        $fullUrl = $request->fullUrl();
        
        try {
            // For development, we can test immediately
            if (app()->environment('local', 'testing')) {
                $this->accessibilityService->runAccessibilityTest($fullUrl);
            } else {
                // In production, queue for processing
                $this->accessibilityService->runAccessibilityTest($fullUrl);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to queue accessibility test', [
                'url' => $fullUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if response is HTML
     */
    private function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        
        return str_contains($contentType, 'text/html') || 
               str_contains($contentType, 'text/plain');
    }

    /**
     * Add accessibility headers to response
     */
    private function addAccessibilityHeaders(Response $response): Response
    {
        // Add accessibility-related headers
        $response->headers->set('X-Accessibility-Tested', 'true');
        $response->headers->set('X-WCAG-Version', '2.1');
        $response->headers->set('X-Compliance-Level', 'AA');
        
        // Add CSP header for security (if not already set)
        if (!$response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', 
                "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
        }
        
        return $response;
    }

    /**
     * Get accessibility status for a page
     */
    public function getPageAccessibilityStatus(string $url): array
    {
        return $this->accessibilityService->getComplianceSummary($url);
    }

    /**
     * Validate accessibility compliance in real-time
     */
    public function validateRealTimeAccessibility(Request $request): array
    {
        $issues = [];
        
        // Check for common accessibility issues in request
        $issues = array_merge($issues, $this->checkColorContrast($request));
        $issues = array_merge($issues, $this->checkAltText($request));
        $issues = array_merge($issues, $this->checkKeyboardNavigation($request));
        $issues = array_merge($issues, $this->checkFormAccessibility($request));
        
        return [
            'is_compliant' => empty($issues),
            'issues' => $issues,
            'wcag_level' => 'AA',
            'score' => $this->calculateRealTimeScore($issues),
        ];
    }

    /**
     * Check color contrast issues
     */
    private function checkColorContrast(Request $request): array
    {
        $issues = [];
        $body = $request->getContent();
        
        // Check for inline styles that might have low contrast
        if (preg_match_all('/color:\s*([^;]+);/i', $body, $matches)) {
            foreach ($matches[1] as $color) {
                $color = trim($color);
                // Simplified check - in reality, you'd parse RGB/hex values
                if (str_contains(strtolower($color), 'gray') || 
                    str_contains(strtolower($color), '#999') ||
                    str_contains(strtolower($color), '#666')) {
                    $issues[] = [
                        'type' => 'color_contrast',
                        'severity' => 'warning',
                        'message' => 'Potential low contrast color detected: ' . $color,
                        'wcag_criterion' => '1.4.3',
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Check for missing alt text
     */
    private function checkAltText(Request $request): array
    {
        $issues = [];
        $body = $request->getContent();
        
        // Check for images without alt attributes
        if (preg_match_all('/<img(?![^>]*alt=)[^>]*>/i', $body, $matches)) {
            foreach ($matches[0] as $imgTag) {
                $issues[] = [
                    'type' => 'missing_alt_text',
                    'severity' => 'critical',
                    'message' => 'Image missing alt attribute',
                    'element' => $imgTag,
                    'wcag_criterion' => '1.1.1',
                ];
            }
        }
        
        return $issues;
    }

    /**
     * Check keyboard navigation support
     */
    private function checkKeyboardNavigation(Request $request): array
    {
        $issues = [];
        $body = $request->getContent();
        
        // Check for onclick handlers without keyboard support
        if (preg_match_all('/onclick="[^"]*"/i', $body, $matches)) {
            foreach ($matches[0] as $onclick) {
                $issues[] = [
                    'type' => 'keyboard_accessibility',
                    'severity' => 'warning',
                    'message' => 'Element has click handler but may lack keyboard support',
                    'element' => $onclick,
                    'wcag_criterion' => '2.1.1',
                ];
            }
        }
        
        return $issues;
    }

    /**
     * Check form accessibility
     */
    private function checkFormAccessibility(Request $request): array
    {
        $issues = [];
        $body = $request->getContent();
        
        // Check for inputs without labels
        if (preg_match_all('/<input[^>]*>/i', $body, $inputMatches)) {
            foreach ($inputMatches[0] as $input) {
                if (!preg_match('/<label[^>]*for=/i', $body) && 
                    !preg_match('/aria-label=/i', $input) &&
                    !preg_match('/aria-labelledby=/i', $input)) {
                    $issues[] = [
                        'type' => 'form_label',
                        'severity' => 'critical',
                        'message' => 'Input field missing associated label',
                        'element' => $input,
                        'wcag_criterion' => '1.3.1',
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Calculate real-time accessibility score
     */
    private function calculateRealTimeScore(array $issues): int
    {
        $totalIssues = count($issues);
        $criticalIssues = count(array_filter($issues, fn($issue) => $issue['severity'] === 'critical'));
        $warningIssues = count(array_filter($issues, fn($issue) => $issue['severity'] === 'warning'));
        
        // Score calculation: start at 100, deduct for issues
        $score = 100 - ($criticalIssues * 20) - ($warningIssues * 5);
        
        return max(0, $score);
    }

    /**
     * Get accessibility recommendations
     */
    public function getAccessibilityRecommendations(string $url): array
    {
        $compliance = $this->accessibilityService->getComplianceSummary($url);
        
        $recommendations = [];
        
        if ($compliance['score'] < 100) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Improve Overall Accessibility Score',
                'description' => 'Address all identified violations to reach 100% compliance.',
                'wcag_criteria' => ['1.1.1', '1.3.1', '2.1.1', '1.4.3'],
            ];
        }
        
        if (!empty($compliance['violations'])) {
            $violationTypes = array_unique(array_column($compliance['violations'], 'id'));
            
            foreach ($violationTypes as $violation) {
                $recommendations[] = $this->getRecommendationForViolation($violation);
            }
        }
        
        return $recommendations;
    }

    /**
     * Get recommendation for specific violation type
     */
    private function getRecommendationForViolation(string $violation): array
    {
        $recommendations = [
            'color-contrast' => [
                'priority' => 'high',
                'title' => 'Improve Color Contrast',
                'description' => 'Ensure all text has sufficient color contrast ratio (4.5:1 for normal text, 3:1 for large text).',
                'wcag_criteria' => ['1.4.3'],
                'implementation_tips' => [
                    'Use high contrast color combinations',
                    'Test contrast ratios with tools like WebAIM Color Contrast Checker',
                    'Consider dark mode alternatives for better contrast',
                ],
            ],
            'image-alt' => [
                'priority' => 'critical',
                'title' => 'Add Alternative Text to Images',
                'description' => 'All images must have meaningful alternative text for screen readers.',
                'wcag_criteria' => ['1.1.1'],
                'implementation_tips' => [
                    'Write descriptive alt text that conveys the image\'s purpose',
                    'Use empty alt="" for decorative images',
                    'Consider context when writing alt text for charts and graphs',
                ],
            ],
            'button-name' => [
                'priority' => 'high',
                'title' => 'Ensure Button Accessibility',
                'description' => 'All buttons must have accessible names for screen reader users.',
                'wcag_criteria' => ['4.1.2'],
                'implementation_tips' => [
                    'Use text content inside buttons',
                    'Use aria-label for icon-only buttons',
                    'Avoid using images without proper text alternatives',
                ],
            ],
        ];
        
        return $recommendations[$violation] ?? [
            'priority' => 'medium',
            'title' => "Address {$violation} Issue",
            'description' => 'Review and fix the identified accessibility issue.',
            'wcag_criteria' => [],
        ];
    }
}