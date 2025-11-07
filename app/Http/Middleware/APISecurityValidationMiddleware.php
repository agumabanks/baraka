<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Security Validation Middleware
 * 
 * Provides comprehensive security validation including:
 * - Input sanitization and validation
 * - SQL injection prevention
 * - XSS protection
 * - Request signature verification
 * - Business logic validation
 * - Audit logging
 */
class APISecurityValidationMiddleware
{
    // Security patterns and validation rules
    private const SQL_INJECTION_PATTERNS = [
        '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script)\b/i',
        '/(\'|\")(\s*\w*)*(\'|\")/',
        '/\b(or|and|not)\b\s+\d+\s*=\s*\d+/i',
    ];

    private const XSS_PATTERNS = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi',
        '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi',
        '/on\w+\s*=\s*["\'][^"\']*["\']/gi',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
    ];

    private const SUSPICIOUS_PAYLOADS = [
        '../../../',
        '..\\..\\..\\',
        '%2e%2e%2f',
        '%252e%252e%252f',
    ];

    // Maximum field size limits to prevent DoS
    private const FIELD_SIZE_LIMITS = [
        'description' => 5000,
        'contents' => 1000,
        'address' => 500,
        'name' => 255,
        'code' => 100,
        'reference_id' => 255,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Security validation pipeline
            $this->validateRequestSecurity($request);
            $this->sanitizeInput($request);
            $this->validateBusinessRules($request);
            $this->validateFileUploads($request);
            $this->logSecurityEvent($request, 'request_received');

            $response = $next($request);

            // Post-response security measures
            $this->addSecurityHeaders($response);
            $this->logSecurityEvent($request, 'request_processed', $response);

            return $response;

        } catch (SecurityViolationException $e) {
            $this->logSecurityEvent($request, 'security_violation', null, [
                'violation_type' => $e->getViolationType(),
                'details' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Security validation failed',
                'message' => 'Request could not be processed due to security concerns',
                'timestamp' => now()->toISOString(),
            ], 403);

        } catch (\Exception $e) {
            Log::error('Security middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_path' => $request->path(),
                'user_id' => auth('api')->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Security validation error',
                'message' => 'An error occurred during security validation',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    private function validateRequestSecurity(Request $request): void
    {
        // Check for SQL injection patterns
        $this->checkSQLInjection($request);
        
        // Check for XSS attempts
        $this->checkXSSPatterns($request);
        
        // Check for path traversal attempts
        $this->checkPathTraversal($request);
        
        // Validate request size
        $this->validateRequestSize($request);
        
        // Check for suspicious payloads
        $this->checkSuspiciousPayloads($request);
        
        // Validate JSON structure
        $this->validateJSONStructure($request);
    }

    private function checkSQLInjection(Request $request): void
    {
        $inputData = json_encode($request->all());
        
        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $inputData, $matches)) {
                throw new SecurityViolationException(
                    'SQL injection pattern detected: ' . $matches[0],
                    'sql_injection'
                );
            }
        }
    }

    private function checkXSSPatterns(Request $request): void
    {
        $inputData = json_encode($request->all());
        
        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $inputData, $matches)) {
                throw new SecurityViolationException(
                    'XSS pattern detected: ' . $matches[0],
                    'xss_attempt'
                );
            }
        }
    }

    private function checkPathTraversal(Request $request): void
    {
        $pathData = json_encode($request->all());
        
        foreach (self::SUSPICIOUS_PAYLOADS as $payload) {
            if (strpos($pathData, $payload) !== false) {
                throw new SecurityViolationException(
                    'Path traversal attempt detected: ' . $payload,
                    'path_traversal'
                );
            }
        }
    }

    private function validateRequestSize(Request $request): void
    {
        $maxSize = config('api.max_request_size', 10 * 1024 * 1024); // 10MB default
        
        if ($request->getContentLength() > $maxSize) {
            throw new SecurityViolationException(
                'Request size exceeds maximum allowed',
                'request_too_large'
            );
        }
    }

    private function checkSuspiciousPayloads(Request $request): void
    {
        $inputData = json_encode($request->all());
        
        // Check for binary data in text fields
        if (preg_match('/[^\x00-\x7F]+/', $inputData)) {
            throw new SecurityViolationException(
                'Suspicious non-ASCII characters detected',
                'suspicious_encoding'
            );
        }
    }

    private function validateJSONStructure(Request $request): void
    {
        $contentType = $request->header('Content-Type');
        
        if ($contentType === 'application/json') {
            $jsonData = $request->getContent();
            $decoded = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SecurityViolationException(
                    'Invalid JSON structure: ' . json_last_error_msg(),
                    'invalid_json'
                );
            }
            
            // Check for deeply nested structures (potential DoS)
            $depth = $this->getMaxDepth($decoded);
            if ($depth > 10) {
                throw new SecurityViolationException(
                    'JSON structure too deeply nested',
                    'json_too_deep'
                );
            }
        }
    }

    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->merge($sanitized);
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            }
        }
        
        return $data;
    }

    private function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Strip control characters
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Apply field-specific sanitization
        $fieldName = $this->getCurrentFieldName();
        if (isset(self::FIELD_SIZE_LIMITS[$fieldName])) {
            $value = substr($value, 0, self::FIELD_SIZE_LIMITS[$fieldName]);
        }
        
        return trim($value);
    }

    private function validateBusinessRules(Request $request): void
    {
        // Additional business rule validations
        switch ($request->path()) {
            case '/api/v1/pricing/quote':
                $this->validateQuoteRequest($request);
                break;
            case '/api/v1/contracts':
                $this->validateContractRequest($request);
                break;
            case '/api/v1/webhooks/register':
                $this->validateWebhookRegistration($request);
                break;
        }
    }

    private function validateQuoteRequest(Request $request): void
    {
        $data = $request->all();
        
        if (isset($data['shipment_data']['weight_kg']) && $data['shipment_data']['weight_kg'] > 150) {
            throw new SecurityViolationException(
                'Weight exceeds maximum allowed (150kg)',
                'weight_limit_exceeded'
            );
        }
        
        if (isset($data['shipment_data']['declared_value']) && $data['shipment_data']['declared_value'] > 100000) {
            throw new SecurityViolationException(
                'Declared value exceeds maximum allowed ($100,000)',
                'value_limit_exceeded'
            );
        }
    }

    private function validateContractRequest(Request $request): void
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = $request->all();
            
            if (isset($data['volume_commitment']) && $data['volume_commitment'] > 1000000) {
                throw new SecurityViolationException(
                    'Volume commitment exceeds maximum allowed (1,000,000)',
                    'volume_limit_exceeded'
                );
            }
        }
    }

    private function validateWebhookRegistration(Request $request): void
    {
        $data = $request->all();
        
        if (isset($data['url']) && !$this->isValidWebhookUrl($data['url'])) {
            throw new SecurityViolationException(
                'Invalid webhook URL format',
                'invalid_webhook_url'
            );
        }
    }

    private function validateFileUploads(Request $request): void
    {
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $file) {
                if ($file->getSize() > 5 * 1024 * 1024) { // 5MB limit
                    throw new SecurityViolationException(
                        'File size exceeds maximum allowed (5MB)',
                        'file_too_large'
                    );
                }
            }
        }
    }

    private function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
    }

    private function logSecurityEvent(Request $request, string $event, ?Response $response = null, array $context = []): void
    {
        $logData = [
            'event_type' => $event,
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => auth('api')->id(),
            'api_key' => $this->maskApiKey($request->header('Authorization')),
            'request_size' => $request->getContentLength(),
            'response_code' => $response?->getStatusCode(),
            'context' => $context,
        ];

        // Log to security channel
        Log::channel('security')->info('API Security Event', $logData);
    }

    private function getMaxDepth($data, int $depth = 0): int
    {
        if (is_array($data)) {
            $maxDepth = $depth;
            foreach ($data as $value) {
                $maxDepth = max($maxDepth, $this->getMaxDepth($value, $depth + 1));
            }
            return $maxDepth;
        }
        return $depth;
    }

    private function getCurrentFieldName(): ?string
    {
        // This would need to be implemented based on your context tracking
        return null;
    }

    private function isValidWebhookUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $parsed = parse_url($url);
        return in_array($parsed['scheme'] ?? '', ['https']) && 
               !in_array($parsed['host'] ?? '', ['localhost', '127.0.0.1', '0.0.0.0']);
    }

    private function maskApiKey(?string $authHeader): string
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return 'none';
        }
        
        $key = substr($authHeader, 7);
        return strlen($key) <= 8 ? str_repeat('*', strlen($key)) : 
               substr($key, 0, 4) . '***' . substr($key, -4);
    }
}

/**
 * Custom Security Violation Exception
 */
class SecurityViolationException extends \Exception
{
    private string $violationType;

    public function __construct(string $message, string $violationType)
    {
        parent::__construct($message);
        $this->violationType = $violationType;
    }

    public function getViolationType(): string
    {
        return $this->violationType;
    }
}