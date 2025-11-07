<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\AccessibilityAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditLoggingMiddleware
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log the request if it's a state-changing operation
        if ($this->shouldLogRequest($request)) {
            $this->auditService->logApiRequest(
                method: $request->method(),
                endpoint: $request->route()?->getName() ?? $request->path(),
                requestData: $this->sanitizeRequestData($request->all()),
                responseCode: 200, // Will be updated in terminate method
                duration: 0 // Will be calculated in terminate method
            );
        }

        $response = $next($request);

        // Log response in terminate method for accurate timing
        return $response;
    }

    /**
     * Handle response after request is complete
     */
    public function terminate(Request $request, Response $response): void
    {
        $endTime = microtime(true);
        $duration = $endTime - $this->getStartTime($request);

        // Log detailed API request information
        if ($this->shouldLogRequest($request)) {
            $this->auditService->logApiRequest(
                method: $request->method(),
                endpoint: $request->route()?->getName() ?? $request->path(),
                requestData: $this->sanitizeRequestData($request->all()),
                responseData: $this->extractResponseData($response),
                responseCode: $response->getStatusCode(),
                duration: $duration
            );
        }

        // Log authentication events
        if ($this->isAuthRequest($request)) {
            $this->auditService->logAuthentication(
                action: $this->mapAuthAction($request),
                metadata: [
                    'endpoint' => $request->path(),
                    'response_code' => $response->getStatusCode(),
                    'duration_ms' => round($duration * 1000, 2),
                ],
                severity: $response->getStatusCode() >= 400 ? 'warning' : 'info'
            );
        }

        // Log pricing-related requests
        if ($this->isPricingRequest($request)) {
            $pricingData = $request->only([
                'quote_id', 'calculation_method', 'total_amount', 
                'currency', 'rules_applied'
            ]);
            
            $this->auditService->logPricingAction(
                actionType: $this->mapPricingAction($request),
                pricingData: $pricingData,
                module: 'api'
            );
        }

        // Log contract-related requests
        if ($this->isContractRequest($request)) {
            $contractData = $request->only([
                'contract_id', 'contract_type', 'effective_date', 
                'expiration_date', 'total_value'
            ]);
            
            $this->auditService->logContractAction(
                actionType: $this->mapContractAction($request),
                contractData: $contractData,
                module: 'api'
            );
        }
    }

    /**
     * Determine if request should be logged
     */
    private function shouldLogRequest(Request $request): bool
    {
        $stateChangingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        
        // Always log state-changing operations
        if (in_array($request->method(), $stateChangingMethods)) {
            return true;
        }

        // Log sensitive GET requests (e.g., user data, financial data)
        $sensitiveEndpoints = [
            '/api/v1/user',
            '/api/v1/customer',
            '/api/v1/merchant',
            '/api/v1/payment',
            '/api/v1/financial',
        ];

        return collect($sensitiveEndpoints)->some(function ($endpoint) use ($request) {
            return str_contains($request->path(), $endpoint);
        });
    }

    /**
     * Sanitize request data for logging
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password', 'token', 'api_key', 'secret', 'key',
            'credit_card', 'ssn', 'sin', 'iban', 'swift'
        ];

        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Limit data size
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 10000) {
            $data = [
                '_truncated' => true,
                '_original_size' => strlen($jsonData),
                '_fields_count' => count($data)
            ];
        }

        return $data;
    }

    /**
     * Extract response data for logging
     */
    private function extractResponseData(Response $response): ?array
    {
        if ($response->getStatusCode() >= 400) {
            $content = $response->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Check if request is authentication-related
     */
    private function isAuthRequest(Request $request): bool
    {
        $authPaths = ['/login', '/logout', '/register', '/password', '/verify'];
        
        return collect($authPaths)->some(function ($path) use ($request) {
            return str_contains($request->path(), $path);
        });
    }

    /**
     * Map request to authentication action
     */
    private function mapAuthAction(Request $request): string
    {
        if (str_contains($request->path(), 'login')) {
            return $request->method() === 'POST' ? 'login' : 'login_attempt';
        }
        
        if (str_contains($request->path(), 'logout')) {
            return 'logout';
        }
        
        if (str_contains($request->path(), 'register')) {
            return 'register';
        }
        
        if (str_contains($request->path(), 'password')) {
            return 'password_change';
        }

        return 'auth_action';
    }

    /**
     * Check if request is pricing-related
     */
    private function isPricingRequest(Request $request): bool
    {
        return str_contains($request->path(), '/pricing/') || 
               str_contains($request->path(), '/quote/');
    }

    /**
     * Map request to pricing action
     */
    private function mapPricingAction(Request $request): string
    {
        if (str_contains($request->path(), 'quote') && $request->isMethod('POST')) {
            return 'create';
        }
        
        if (str_contains($request->path(), 'quote') && $request->isMethod('PUT')) {
            return 'update';
        }
        
        if (str_contains($request->path(), 'quote') && $request->isMethod('GET')) {
            return 'read';
        }

        return 'pricing_action';
    }

    /**
     * Check if request is contract-related
     */
    private function isContractRequest(Request $request): bool
    {
        return str_contains($request->path(), '/contract');
    }

    /**
     * Map request to contract action
     */
    private function mapContractAction(Request $request): string
    {
        if ($request->isMethod('POST')) {
            return 'create';
        }
        
        if ($request->isMethod('PUT')) {
            return 'update';
        }
        
        if ($request->isMethod('DELETE')) {
            return 'delete';
        }

        return 'contract_action';
    }

    /**
     * Get start time from request
     */
    private function getStartTime(Request $request): float
    {
        return $request->attributes->get('request_start_time', microtime(true));
    }
}