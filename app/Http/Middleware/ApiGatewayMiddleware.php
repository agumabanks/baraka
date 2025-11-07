<?php

namespace App\Http\Middleware;

use App\Services\ApiGateway\ApiGatewayService;
use App\Services\ApiGateway\Monitoring\LogCollector;
use App\Services\ApiGateway\Monitoring\MetricsCollector;
use App\Services\ApiGateway\RateLimit\RateLimitService;
use App\Services\ApiGateway\Authentication\AuthenticationService;
use App\Services\ApiGateway\Validation\RequestValidator;
use App\Services\ApiGateway\Transformation\RequestTransformer;
use App\Services\ApiGateway\CircuitBreakerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class ApiGatewayMiddleware
{
    protected $gatewayService;
    protected $logCollector;
    protected $metricsCollector;
    protected $rateLimitService;
    protected $authService;
    protected $requestValidator;
    protected $requestTransformer;
    protected $circuitBreaker;

    public function __construct()
    {
        $this->gatewayService = new ApiGatewayService();
        $this->logCollector = new LogCollector();
        $this->metricsCollector = new MetricsCollector();
        $this->rateLimitService = new RateLimitService();
        $this->authService = new AuthenticationService();
        $this->requestValidator = new RequestValidator();
        $this->requestTransformer = new RequestTransformer();
        $this->circuitBreaker = new CircuitBreakerService();
    }

    /**
     * Handle an incoming API request through the gateway
     */
    public function handle(Request $request, \Closure $next)
    {
        $requestId = $this->generateRequestId();
        $startTime = microtime(true);
        $clientIp = $this->getClientIp($request);
        
        // Attach request context
        $context = $this->buildRequestContext($request, $requestId, $clientIp);
        
        try {
            // 1. Log incoming request
            $this->logCollector->logRequest($context);
            
            // 2. Record request metrics
            $this->metricsCollector->recordRequestStart($context);
            
            // 3. Validate request
            if (!$this->isSkipValidation($request->path())) {
                $validationResult = $this->requestValidator->validate($request, $context);
                if (!$validationResult['isValid']) {
                    return $this->createErrorResponse(
                        422,
                        'VALIDATION_ERROR',
                        'Request validation failed',
                        $validationResult['errors'],
                        $context
                    );
                }
            }
            
            // 4. Check authentication
            if (!$this->isSkipAuth($request->path())) {
                $authResult = $this->authService->authenticate($request, $context);
                if (!$authResult['success']) {
                    return $this->createErrorResponse(
                        401,
                        'AUTHENTICATION_FAILED',
                        'Authentication required',
                        $authResult['message'],
                        $context
                    );
                }
                $context['user'] = $authResult['user'];
                $context['permissions'] = $authResult['permissions'];
            }
            
            // 5. Check rate limits
            if (!$this->isSkipRateLimit($request->path())) {
                $rateLimitResult = $this->rateLimitService->checkLimit($request, $context);
                if (!$rateLimitResult['allowed']) {
                    return $this->createErrorResponse(
                        429,
                        'RATE_LIMIT_EXCEEDED',
                        'Rate limit exceeded',
                        $rateLimitResult['message'],
                        $context
                    );
                }
            }
            
            // 6. Transform request
            $transformedRequest = $this->requestTransformer->transform($request, $context);
            
            // 7. Check circuit breaker
            $targetService = $this->determineTargetService($request);
            if (!$this->circuitBreaker->isAvailable($targetService)) {
                return $this->createErrorResponse(
                    503,
                    'SERVICE_UNAVAILABLE',
                    'Service temporarily unavailable',
                    "Circuit breaker is open for service: {$targetService}",
                    $context
                );
            }
            
            // 8. Route to backend service
            $response = $this->routeToService($transformedRequest, $context, $targetService);
            
            // 9. Transform response
            $transformedResponse = $this->requestTransformer->transformResponse($response, $context);
            
            // 10. Record successful request metrics
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->metricsCollector->recordRequestSuccess($context, $processingTime);
            
            // 11. Log successful response
            $this->logCollector->logResponse($context, $response);
            
            return $transformedResponse;
            
        } catch (\Exception $e) {
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // 12. Record error metrics
            $this->metricsCollector->recordRequestError($context, $processingTime, $e);
            
            // 13. Log error
            $this->logCollector->logError($context, $e);
            
            // 14. Update circuit breaker on failure
            if (isset($targetService)) {
                $this->circuitBreaker->recordFailure($targetService);
            }
            
            return $this->createErrorResponse(
                500,
                'INTERNAL_ERROR',
                'Internal server error',
                config('app.debug') ? $e->getMessage() : 'An error occurred',
                $context
            );
        }
    }

    /**
     * Route request to appropriate backend service
     */
    protected function routeToService(Request $request, array $context, string $targetService): Response
    {
        $serviceConfig = config("api_gateway.services.{$targetService}");
        
        if (!$serviceConfig) {
            throw new \Exception("Service configuration not found: {$targetService}");
        }
        
        $url = $this->buildServiceUrl($serviceConfig, $request);
        $timeout = $serviceConfig['timeout'] ?? 30;
        
        try {
            $client = Http::timeout($timeout)
                ->connectTimeout($serviceConfig['connect_timeout'] ?? 10)
                ->retry(3, 100); // Retry 3 times with 100ms delay
            
            // Add authentication headers if needed
            if (isset($context['user']) && $this->needsAuthentication($targetService)) {
                $client->withHeaders([
                    'Authorization' => $this->generateServiceToken($context['user']),
                    'X-User-ID' => $context['user']['id'] ?? '',
                ]);
            }
            
            // Add custom headers
            $client->withHeaders([
                'X-Request-ID' => $context['requestId'],
                'X-Client-IP' => $context['clientIp'],
                'X-Processing-Start' => now()->toISOString(),
            ]);
            
            // Add request context as headers
            $client->withHeaders([
                'X-User-Agent' => $request->userAgent() ?? '',
                'X-Accept' => $request->header('Accept', 'application/json'),
            ]);
            
            // Make the request
            $httpResponse = match(strtoupper($request->method())) {
                'GET' => $client->get($url, $this->getQueryParams($request)),
                'POST' => $client->post($url, $this->getRequestData($request)),
                'PUT' => $client->put($url, $this->getRequestData($request)),
                'PATCH' => $client->patch($url, $this->getRequestData($request)),
                'DELETE' => $client->delete($url, $this->getRequestData($request)),
                'HEAD' => $client->head($url, $this->getQueryParams($request)),
                'OPTIONS' => $client->options($url, $this->getRequestData($request)),
                default => throw new \Exception("Unsupported HTTP method: {$request->method()}")
            };
            
            // Record successful service call
            $this->circuitBreaker->recordSuccess($targetService);
            
            return response($httpResponse->body(), $httpResponse->status())
                ->withHeaders($httpResponse->headers());
                
        } catch (\Exception $e) {
            // Record service failure
            $this->circuitBreaker->recordFailure($targetService);
            
            throw $e;
        }
    }

    /**
     * Determine target service for the request
     */
    protected function determineTargetService(Request $request): string
    {
        $path = $request->path();
        
        // Route based on path patterns
        if (str_starts_with($path, '/api/v1/operational')) {
            return 'operational-reporting';
        } elseif (str_starts_with($path, '/api/v1/financial')) {
            return 'financial-reporting';
        } elseif (str_starts_with($path, '/api/v1/customer')) {
            return 'customer-intelligence';
        } elseif (str_starts_with($path, '/api/v1/dashboard')) {
            return 'real-time-dashboard';
        }
        
        // Default to operational reporting
        return 'operational-reporting';
    }

    /**
     * Build service URL
     */
    protected function buildServiceUrl(array $serviceConfig, Request $request): string
    {
        $protocol = $serviceConfig['protocol'] ?? 'http';
        $host = $serviceConfig['host'] ?? '127.0.0.1';
        $port = $serviceConfig['port'] ?? 80;
        $path = $this->getServicePath($request);
        
        $url = "{$protocol}://{$host}:{$port}{$path}";
        
        Log::debug("Routing request to service URL: {$url}");
        
        return $url;
    }

    /**
     * Get service path from request
     */
    protected function getServicePath(Request $request): string
    {
        $path = $request->path();
        
        // Remove API version prefix
        if (preg_match('/^\/api\/v\d+\/([^\/]+)\/?(.*)$/', $path, $matches)) {
            return '/' . ($matches[2] ?? '');
        }
        
        return $path;
    }

    /**
     * Get query parameters from request
     */
    protected function getQueryParams(Request $request): array
    {
        return $request->query();
    }

    /**
     * Get request data
     */
    protected function getRequestData(Request $request): array
    {
        $data = $request->all();
        
        // Remove Laravel-specific data
        unset($data['_token'], $data['_method']);
        
        return $data;
    }

    /**
     * Check if service needs authentication
     */
    protected function needsAuthentication(string $serviceName): bool
    {
        // Customize based on service requirements
        return in_array($serviceName, [
            'financial-reporting',
            'customer-intelligence'
        ]);
    }

    /**
     * Generate service token for backend authentication
     */
    protected function generateServiceToken(array $user): string
    {
        // Generate a JWT or API key for backend service
        return 'service-token-' . base64_encode(json_encode([
            'user_id' => $user['id'] ?? '',
            'timestamp' => time(),
        ]));
    }

    /**
     * Build request context
     */
    protected function buildRequestContext(Request $request, string $requestId, string $clientIp): array
    {
        return [
            'requestId' => $requestId,
            'clientIp' => $clientIp,
            'method' => $request->method(),
            'path' => $request->path(),
            'query' => $request->query(),
            'headers' => $request->headers->all(),
            'userAgent' => $request->userAgent(),
            'contentType' => $request->getContentType(),
            'contentLength' => $request->header('content-length', 0),
            'timestamp' => now(),
        ];
    }

    /**
     * Get client IP address
     */
    protected function getClientIp(Request $request): string
    {
        return $request->ip();
    }

    /**
     * Generate unique request ID
     */
    protected function generateRequestId(): string
    {
        return 'req_' . uniqid() . '_' . microtime(true);
    }

    /**
     * Create error response
     */
    protected function createErrorResponse(int $statusCode, string $errorCode, string $message, string $details, array $context): Response
    {
        $response = [
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details,
                'timestamp' => now()->toISOString(),
                'requestId' => $context['requestId'],
            ]
        ];
        
        return response()->json($response, $statusCode, [
            'X-Request-ID' => $context['requestId'],
            'X-Error-Code' => $errorCode,
        ]);
    }

    /**
     * Check if rate limiting should be skipped
     */
    protected function isSkipRateLimit(string $path): bool
    {
        $skipPaths = config('api_gateway.skip_rate_limit_paths', []);
        return in_array($path, $skipPaths);
    }

    /**
     * Check if authentication should be skipped
     */
    protected function isSkipAuth(string $path): bool
    {
        $skipPaths = config('api_gateway.skip_auth_paths', []);
        return in_array($path, $skipPaths);
    }

    /**
     * Check if validation should be skipped
     */
    protected function isSkipValidation(string $path): bool
    {
        $skipPaths = config('api_gateway.skip_validation_paths', []);
        return in_array($path, $skipPaths);
    }
}