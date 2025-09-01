<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DHLSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // DHL Security Headers
        $this->setSecurityHeaders($request);

        // Input validation and sanitization
        $this->validateAndSanitizeInput($request);

        // Audit logging for sensitive operations
        $this->logSensitiveOperations($request);

        // Check for suspicious patterns
        if ($this->detectSuspiciousActivity($request)) {
            $this->handleSuspiciousActivity($request);
        }

        // Data encryption for sensitive data
        $this->handleSensitiveData($request);

        $response = $next($request);

        // Add additional security headers to response
        $this->addResponseSecurityHeaders($response);

        return $response;
    }

    /**
     * Set comprehensive security headers
     */
    private function setSecurityHeaders(Request $request): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://trusted-cdn.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "connect-src 'self' https://api.dhl.com; " .
               "frame-ancestors 'none';";

        header("Content-Security-Policy: $csp");

        // HSTS (HTTP Strict Transport Security)
        if ($request->secure()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    /**
     * Validate and sanitize input data
     */
    private function validateAndSanitizeInput(Request $request): void
    {
        $sensitiveFields = ['password', 'ssn', 'credit_card', 'bank_account'];

        foreach ($request->all() as $key => $value) {
            // Skip file uploads
            if ($request->hasFile($key)) {
                continue;
            }

            // Sanitize input
            if (is_string($value)) {
                $sanitized = $this->sanitizeInput($value);

                // Check for sensitive data patterns
                if (in_array($key, $sensitiveFields) || $this->containsSensitiveData($value)) {
                    // Encrypt sensitive data before processing
                    $request->merge([$key => $this->encryptSensitiveData($sanitized)]);
                } else {
                    $request->merge([$key => $sanitized]);
                }
            }
        }
    }

    /**
     * Sanitize input data
     */
    private function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", "", $input);

        // Remove potentially dangerous characters
        $input = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        // Limit input length
        if (strlen($input) > 10000) {
            $input = substr($input, 0, 10000);
        }

        return $input;
    }

    /**
     * Check if input contains sensitive data patterns
     */
    private function containsSensitiveData(string $input): bool
    {
        $patterns = [
            '/\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}/', // Credit card numbers
            '/\d{3}[\s-]?\d{2}[\s-]?\d{4}/', // SSN pattern
            '/\b\d{10,12}\b/', // Bank account numbers
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encrypt sensitive data
     */
    private function encryptSensitiveData(string $data): string
    {
        // Use Laravel's encryption
        return encrypt($data);
    }

    /**
     * Log sensitive operations for audit trail
     */
    private function logSensitiveOperations(Request $request): void
    {
        $sensitiveRoutes = [
            'admin.users.store',
            'admin.users.update',
            'admin.payment.*',
            'admin.settings.*'
        ];

        $currentRoute = $request->route() ? $request->route()->getName() : '';

        foreach ($sensitiveRoutes as $route) {
            if (fnmatch($route, $currentRoute)) {
                Log::info('Sensitive operation performed', [
                    'user_id' => auth()->id(),
                    'route' => $currentRoute,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now(),
                    'request_data' => $this->sanitizeLogData($request->all())
                ]);
                break;
            }
        }
    }

    /**
     * Sanitize data for logging
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key'];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Detect suspicious activity
     */
    private function detectSuspiciousActivity(Request $request): bool
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Check for rapid requests (potential DoS)
        $requestCount = Cache::get("requests:$ip", 0);
        if ($requestCount > 100) { // More than 100 requests per minute
            return true;
        }
        Cache::increment("requests:$ip", 1, 60); // Expire in 60 seconds

        // Check for suspicious user agents
        $suspiciousAgents = ['sqlmap', 'nmap', 'nikto', 'dirbuster'];
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }

        // Check for SQL injection patterns
        $sqlPatterns = ['union select', 'information_schema', 'script>', '<script'];
        $queryString = $request->getQueryString();
        if ($queryString) {
            foreach ($sqlPatterns as $pattern) {
                if (stripos($queryString, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Handle suspicious activity
     */
    private function handleSuspiciousActivity(Request $request): void
    {
        Log::warning('Suspicious activity detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'timestamp' => now()
        ]);

        // Could implement additional measures like IP blocking
    }

    /**
     * Handle sensitive data operations
     */
    private function handleSensitiveData(Request $request): void
    {
        // Ensure sensitive data is properly handled
        if ($request->has(['credit_card', 'bank_account'])) {
            // Additional validation for financial data
            $request->merge(['data_classification' => 'sensitive']);
        }
    }

    /**
     * Add security headers to response
     */
    private function addResponseSecurityHeaders($response): void
    {
        $response->headers->set('X-DHL-Security', 'enabled');
        $response->headers->set('X-Content-Security-Policy', "default-src 'self'");
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }
}
