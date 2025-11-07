<?php

namespace App\Http\Middleware;

use App\Services\Security\RBACService;
use App\Services\Security\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class EnhancedApiSecurityMiddleware
{
    private RBACService $rbacService;
    private const MAX_REQUESTS_PER_MINUTE = 100;
    private const MAX_REQUESTS_PER_HOUR = 1000;
    private const MAX_FAILED_ATTEMPTS = 5;
    private const BLOCK_DURATION = 900; // 15 minutes

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next, $requiredPermission = null)
    {
        try {
            // 1. Validate request format
            $this->validateRequestFormat($request);
            
            // 2. Check rate limiting
            $this->checkRateLimit($request);
            
            // 3. Validate input
            $this->validateInput($request);
            
            // 4. Check permissions if required
            if ($requiredPermission) {
                $this->checkPermission($request, $requiredPermission);
            }
            
            // 5. Check for suspicious patterns
            $this->checkSuspiciousPatterns($request);
            
            // 6. Add security headers
            $response = $next($request);
            $this->addSecurityHeaders($response, $request);
            
            // 7. Log successful API access
            $this->logApiAccess($request, 'success');
            
            return $response;
            
        } catch (Exception $e) {
            $this->handleSecurityViolation($request, $e);
            throw $e;
        }
    }

    /**
     * Validate request format and structure
     */
    private function validateRequestFormat(Request $request): void
    {
        // Check for malformed JSON
        if ($request->isJson() && $request->getContent()) {
            json_decode($request->getContent());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in request body');
            }
        }
        
        // Check for oversized payloads
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($request->getContentSize() > $maxSize) {
            throw new Exception('Request payload too large');
        }
        
        // Check for missing required headers
        $this->validateRequiredHeaders($request);
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(Request $request): void
    {
        $user = $request->user();
        $identifier = $user ? $user->id : $request->ip();
        
        // Per-minute limit
        $minuteKey = "rate_limit_minute_{$identifier}";
        $minuteCount = Cache::get($minuteKey, 0);
        
        if ($minuteCount >= self::MAX_REQUESTS_PER_MINUTE) {
            $this->blockRequest($request, 'Rate limit exceeded (per minute)');
        }
        
        // Per-hour limit
        $hourKey = "rate_limit_hour_{$identifier}";
        $hourCount = Cache::get($hourKey, 0);
        
        if ($hourCount >= self::MAX_REQUESTS_PER_HOUR) {
            $this->blockRequest($request, 'Rate limit exceeded (per hour)');
        }
        
        // Increment counters
        Cache::put($minuteKey, $minuteCount + 1, 60);
        Cache::put($hourKey, $hourCount + 1, 3600);
    }

    /**
     * Validate input data
     */
    private function validateInput(Request $request): void
    {
        // Check for SQL injection patterns
        $this->checkSqlInjection($request);
        
        // Check for XSS patterns
        $this->checkXssPatterns($request);
        
        // Check for command injection
        $this->checkCommandInjection($request);
        
        // Validate data types and formats
        $this->validateDataTypes($request);
    }

    /**
     * Check for SQL injection patterns
     */
    private function checkSqlInjection(Request $request): void
    {
        $sqlPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bdrop\s+table\b)/i',
            '/(\bdelete\s+from\b)/i',
            '/(\binsert\s+into\b)/i',
            '/(\bupdate\s+\w+\s+set\b)/i',
            '/(\bselect\s+\*+\s+from\b)/i',
            '/(\b--|\b#|\b\/\*)/',
            '/(\bexec\b|\bexecute\b)/i',
        ];
        
        $searchData = $this->getRequestDataForValidation($request);
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $searchData)) {
                $this->recordSecurityViolation($request, 'SQL injection attempt detected', 'high');
                throw new Exception('Request contains suspicious patterns');
            }
        }
    }

    /**
     * Check for XSS patterns
     */
    private function checkXssPatterns(Request $request): void
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi',
        ];
        
        $searchData = $this->getRequestDataForValidation($request);
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $searchData)) {
                $this->recordSecurityViolation($request, 'XSS attempt detected', 'high');
                throw new Exception('Request contains XSS patterns');
            }
        }
    }

    /**
     * Check for command injection
     */
    private function checkCommandInjection(Request $request): void
    {
        $commandPatterns = [
            '/\|\s*nc\s+/i',
            '/\|\s*telnet\s+/i',
            '/\|\s*curl\s+/i',
            '/\|\s*wget\s+/i',
            '/;.*(?:\brm\b|\bcp\b|\bmv\b|\bcat\b|\bls\b)/i',
            '/&&.*(?:\brm\b|\bcp\b|\bmv\b|\bcat\b|\bls\b)/i',
        ];
        
        $searchData = $this->getRequestDataForValidation($request);
        
        foreach ($commandPatterns as $pattern) {
            if (preg_match($pattern, $searchData)) {
                $this->recordSecurityViolation($request, 'Command injection attempt detected', 'critical');
                throw new Exception('Request contains command injection patterns');
            }
        }
    }

    /**
     * Validate data types and formats
     */
    private function validateDataTypes(Request $request): void
    {
        // Validate numeric fields
        $numericFields = ['id', 'user_id', 'amount', 'price', 'quantity'];
        foreach ($numericFields as $field) {
            if ($request->has($field) && !is_numeric($request->input($field))) {
                throw new Exception("Field {$field} must be numeric");
            }
        }
        
        // Validate email format
        if ($request->has('email') && !filter_var($request->input('email'), FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Validate UUID format
        $uuidFields = ['uuid', 'transaction_id', 'order_id'];
        foreach ($uuidFields as $field) {
            if ($request->has($field) && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $request->input($field))) {
                throw new Exception("Field {$field} must be a valid UUID");
            }
        }
    }

    /**
     * Check user permissions
     */
    private function checkPermission(Request $request, string $requiredPermission): void
    {
        $user = $request->user();
        
        if (!$user) {
            $this->recordSecurityViolation($request, 'Unauthenticated access attempt', 'medium');
            throw new Exception('Authentication required');
        }
        
        if (!$this->rbacService->hasPermission($user, $requiredPermission)) {
            $this->recordSecurityViolation($request, "Permission denied: {$requiredPermission}", 'medium');
            $this->logApiAccess($request, 'permission_denied');
            abort(403, 'Insufficient permissions');
        }
    }

    /**
     * Check for suspicious patterns
     */
    private function checkSuspiciousPatterns(Request $request): void
    {
        // Check for rapid-fire requests
        $this->checkRapidFire($request);
        
        // Check for unusual user agents
        $this->checkUserAgent($request);
        
        // Check for path traversal attempts
        $this->checkPathTraversal($request);
    }

    /**
     * Check for rapid-fire requests
     */
    private function checkRapidFire(Request $request): void
    {
        $user = $request->user();
        $identifier = $user ? $user->id : $request->ip();
        $cacheKey = "rapid_fire_{$identifier}";
        
        $requests = Cache::get($cacheKey, []);
        $now = microtime(true);
        
        // Remove old requests (older than 10 seconds)
        $requests = array_filter($requests, function ($timestamp) use ($now) {
            return ($now - $timestamp) < 10;
        });
        
        $requests[] = $now;
        Cache::put($cacheKey, $requests, 300);
        
        // If more than 10 requests in 10 seconds, flag as suspicious
        if (count($requests) > 10) {
            $this->recordSecurityViolation($request, 'Rapid-fire request pattern detected', 'high');
        }
    }

    /**
     * Check user agent
     */
    private function checkUserAgent(Request $request): void
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent || strlen($userAgent) < 10) {
            $this->recordSecurityViolation($request, 'Suspicious user agent (missing or too short)', 'low');
        }
        
        // Check for known malicious user agents
        $maliciousAgents = [
            'bot', 'crawler', 'spider', 'scraper', 'sqlmap', 'nmap'
        ];
        
        foreach ($maliciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $this->recordSecurityViolation($request, "Potentially malicious user agent: {$agent}", 'medium');
            }
        }
    }

    /**
     * Check for path traversal attempts
     */
    private function checkPathTraversal(Request $request): void
    {
        $pathTraversalPatterns = [
            '/\.\.\//',
            '/\.\.\\\/',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
        ];
        
        $url = $request->getRequestUri();
        
        foreach ($pathTraversalPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                $this->recordSecurityViolation($request, 'Path traversal attempt detected', 'high');
                throw new Exception('Request contains path traversal patterns');
            }
        }
    }

    /**
     * Add security headers to response
     */
    private function addSecurityHeaders($response, Request $request): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
    }

    /**
     * Block request due to security violation
     */
    private function blockRequest(Request $request, string $reason): void
    {
        $identifier = $request->user() ? $request->user()->id : $request->ip();
        $cacheKey = "blocked_{$identifier}";
        
        Cache::put($cacheKey, true, self::BLOCK_DURATION);
        
        $this->recordSecurityViolation($request, $reason, 'high');
        
        abort(429, 'Request blocked due to security policy');
    }

    /**
     * Handle security violation
     */
    private function handleSecurityViolation(Request $request, Exception $exception): void
    {
        $this->logApiAccess($request, 'security_violation', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Log API access
     */
    private function logApiAccess(Request $request, string $status, array $additionalData = []): void
    {
        SecurityAuditLog::create([
            'event_type' => 'api_access',
            'event_category' => 'security',
            'severity' => 'low',
            'user_id' => $request->user()?->id,
            'user_type' => $request->user() ? get_class($request->user()) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resource_type' => 'api',
            'resource_id' => $request->route()?->getName(),
            'action_details' => array_merge([
                'method' => $request->method(),
                'url' => $request->getRequestUri(),
                'status' => $status,
            ], $additionalData),
            'status' => $status === 'success' ? 'success' : 'warning',
            'description' => "API access: {$request->method()} {$request->getRequestUri()} - {$status}",
        ]);
    }

    /**
     * Record security violation
     */
    private function recordSecurityViolation(Request $request, string $description, string $severity): void
    {
        SecurityAuditLog::create([
            'event_type' => 'security_violation',
            'event_category' => 'security',
            'severity' => $severity,
            'user_id' => $request->user()?->id,
            'user_type' => $request->user() ? get_class($request->user()) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resource_type' => 'api',
            'action_details' => [
                'method' => $request->method(),
                'url' => $request->getRequestUri(),
            ],
            'status' => 'blocked',
            'description' => $description,
        ]);
    }

    /**
     * Get request data for validation
     */
    private function getRequestDataForValidation(Request $request): string
    {
        $data = '';
        
        if ($request->isJson()) {
            $data = $request->getContent();
        } else {
            $data = http_build_query($request->all());
        }
        
        return $data;
    }

    /**
     * Validate required headers
     */
    private function validateRequiredHeaders(Request $request): void
    {
        // API requests should have appropriate content type
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $contentType = $request->header('Content-Type');
            
            if (!$contentType || !str_contains($contentType, ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data'])) {
                throw new Exception('Invalid or missing Content-Type header');
            }
        }
    }
}