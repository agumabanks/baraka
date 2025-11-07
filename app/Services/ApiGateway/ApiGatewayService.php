<?php

namespace App\Services\ApiGateway;

use App\Services\ApiGateway\Middleware\MiddlewareInterface;
use App\Services\ApiGateway\Middleware\RateLimitMiddleware;
use App\Services\ApiGateway\Middleware\AuthMiddleware;
use App\Services\ApiGateway\Middleware\ValidationMiddleware;
use App\Services\ApiGateway\Middleware\TransformationMiddleware;
use App\Services\ApiGateway\Middleware\MonitoringMiddleware;
use App\Services\ApiGateway\Models\ApiRoute;
use App\Services\ApiGateway\Models\ApiVersion;
use App\Services\ApiGateway\Models\RateLimitRule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiGatewayService
{
    protected array $middlewares = [];
    protected array $routes = [];
    protected array $loadBalancedRoutes = [];
    protected $circuitBreaker;

    public function __construct()
    {
        $this->initializeMiddlewares();
        $this->loadRoutes();
        $this->initializeCircuitBreaker();
    }

    /**
     * Initialize middleware stack
     */
    protected function initializeMiddlewares(): void
    {
        $this->middlewares = [
            new RateLimitMiddleware(),
            new AuthMiddleware(),
            new ValidationMiddleware(),
            new TransformationMiddleware(),
            new MonitoringMiddleware(),
        ];
    }

    /**
     * Load API routes from database/cache
     */
    protected function loadRoutes(): void
    {
        $this->routes = Cache::remember('api_gateway_routes', 3600, function () {
            return ApiRoute::with('version', 'rateLimitRules')
                ->where('is_active', true)
                ->get()
                ->keyBy('path')
                ->toArray();
        });

        // Load load-balanced routes
        $this->loadBalancedRoutes = Cache::remember('api_gateway_load_balanced', 3600, function () {
            return DB::table('api_load_balanced_routes')
                ->where('is_active', true)
                ->get()
                ->groupBy('path')
                ->toArray();
        });
    }

    /**
     * Initialize circuit breaker
     */
    protected function initializeCircuitBreaker(): void
    {
        $this->circuitBreaker = new CircuitBreakerService();
    }

    /**
     * Process API request through gateway
     */
    public function processRequest(Request $request): Response
    {
        $requestId = uniqid('req_', true);
        $request->merge(['request_id' => $requestId]);

        try {
            // Load route configuration
            $route = $this->findRoute($request);
            if (!$route) {
                return $this->handleRouteNotFound($request);
            }

            // Check if circuit is open
            if ($this->circuitBreaker->isOpen($route['target_service'])) {
                return $this->handleCircuitOpen($request, $route);
            }

            // Execute middleware chain
            $response = $this->executeMiddlewareChain($request, $route);

            // Record success for circuit breaker
            $this->circuitBreaker->recordSuccess($route['target_service']);

            return $response;

        } catch (\Exception $e) {
            // Record failure for circuit breaker
            $targetService = $this->findRoute($request)['target_service'] ?? 'unknown';
            $this->circuitBreaker->recordFailure($targetService);

            Log::error('API Gateway error', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleError($request, $e);
        }
    }

    /**
     * Find matching route
     */
    protected function findRoute(Request $request): ?array
    {
        $path = $request->path();
        $method = $request->method();

        // Check for load-balanced routes first
        if (isset($this->loadBalancedRoutes[$path])) {
            $loadBalancedRoute = $this->loadBalancedRoutes[$path];
            $targetService = $this->selectLoadBalancedTarget($loadBalancedRoute);
            
            return $this->routes[$path] ?? null;
        }

        // Check regular routes
        if (isset($this->routes[$path])) {
            $route = $this->routes[$path];
            
            // Check HTTP method
            if (in_array($method, $route['allowed_methods'])) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Select load-balanced target service
     */
    protected function selectLoadBalancedTarget(array $loadBalancedRoutes): string
    {
        // Simple round-robin load balancing
        $activeRoutes = array_filter($loadBalancedRoutes, function ($route) {
            return $route['is_healthy'] && $route['weight'] > 0;
        });

        if (empty($activeRoutes)) {
            throw new \Exception('No healthy load-balanced targets available');
        }

        // Pick route with lowest current load
        $selected = $activeRoutes[0];
        foreach ($activeRoutes as $route) {
            if ($route['current_load'] < $selected['current_load']) {
                $selected = $route;
            }
        }

        // Update current load
        DB::table('api_load_balanced_routes')
            ->where('id', $selected['id'])
            ->increment('current_load');

        return $selected['target_service'];
    }

    /**
     * Execute middleware chain
     */
    protected function executeMiddlewareChain(Request $request, array $route): Response
    {
        $context = new ApiGatewayContext($request, $route);

        foreach ($this->middlewares as $middleware) {
            $result = $middleware->handle($context);
            
            if ($result instanceof Response) {
                return $result;
            }

            if (!$result) {
                throw new \Exception("Middleware processing failed");
            }
        }

        // Route to target service
        return $this->routeToService($context);
    }

    /**
     * Route request to target service
     */
    protected function routeToService(ApiGatewayContext $context): Response
    {
        $request = $context->getRequest();
        $route = $context->getRoute();

        // Build target URL
        $targetService = $this->selectTargetService($route);
        $targetUrl = $this->buildTargetUrl($targetService, $request);

        // Create HTTP client
        $client = new \GuzzleHttp\Client([
            'timeout' => $route['timeout'] ?? 30,
            'connect_timeout' => $route['connect_timeout'] ?? 10,
        ]);

        // Prepare request options
        $options = $this->prepareRequestOptions($request, $route);

        try {
            $response = $client->request($request->method(), $targetUrl, $options);
            
            return $this->buildResponse($response, $route);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new \Exception("Failed to connect to service: " . $e->getMessage());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception("Request failed: " . $e->getMessage());
        }
    }

    /**
     * Select target service for route
     */
    protected function selectTargetService(array $route): string
    {
        // Check if route has multiple targets (load balancing)
        if (isset($route['load_balanced']) && $route['load_balanced']) {
            return $this->selectLoadBalancedTarget($route['targets']);
        }

        return $route['target_service'];
    }

    /**
     * Build target URL
     */
    protected function buildTargetUrl(string $targetService, Request $request): string
    {
        $config = config("services.{$targetService}");
        $baseUrl = $config['url'] ?? $config['host'] ?? '';
        
        return rtrim($baseUrl, '/') . '/' . ltrim($request->path(), '/');
    }

    /**
     * Prepare request options for HTTP client
     */
    protected function prepareRequestOptions(Request $request, array $route): array
    {
        $options = [
            'headers' => $this->prepareHeaders($request, $route),
            'body' => $request->getContent(),
        ];

        // Add query parameters
        if ($request->query()) {
            $options['query'] = $request->query();
        }

        // Add authentication
        if ($route['auth_type'] === 'bearer') {
            $options['headers']['Authorization'] = 'Bearer ' . $this->getServiceToken($route['target_service']);
        } elseif ($route['auth_type'] === 'apikey') {
            $options['headers']['X-API-Key'] = $this->getApiKey($route['target_service']);
        }

        return $options;
    }

    /**
     * Prepare request headers
     */
    protected function prepareHeaders(Request $request, array $route): array
    {
        $headers = array_merge($request->headers->all(), [
            'X-Gateway-Request-ID' => $request->request_id,
            'X-Gateway-Timestamp' => time(),
            'X-Forwarded-For' => $request->ip(),
        ]);

        // Remove problematic headers
        unset($headers['host']);
        unset($headers['connection']);

        return $headers;
    }

    /**
     * Build response from service response
     */
    protected function buildResponse(\GuzzleHttp\Psr7\Response $response, array $route): Response
    {
        $content = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        // Remove response headers that should not be forwarded
        unset($headers['transfer-encoding']);
        unset($headers['connection']);

        return new Response($content, $statusCode, $headers);
    }

    /**
     * Handle route not found
     */
    protected function handleRouteNotFound(Request $request): Response
    {
        return response()->json([
            'error' => [
                'code' => 'ROUTE_NOT_FOUND',
                'message' => 'The requested API route was not found',
                'path' => $request->path(),
                'method' => $request->method(),
            ]
        ], 404);
    }

    /**
     * Handle circuit open
     */
    protected function handleCircuitOpen(Request $request, array $route): Response
    {
        return response()->json([
            'error' => [
                'code' => 'SERVICE_UNAVAILABLE',
                'message' => 'Target service is temporarily unavailable',
                'service' => $route['target_service'],
            ]
        ], 503);
    }

    /**
     * Handle errors
     */
    protected function handleError(Request $request, \Exception $e): Response
    {
        return response()->json([
            'error' => [
                'code' => 'GATEWAY_ERROR',
                'message' => 'An error occurred while processing the request',
                'request_id' => $request->request_id,
            ]
        ], 500);
    }

    /**
     * Get service authentication token
     */
    protected function getServiceToken(string $service): string
    {
        return Cache::remember("service_token_{$service}", 300, function () use ($service) {
            $config = config("services.{$service}");
            // Implement service-specific token generation/retrieval
            return $config['token'] ?? '';
        });
    }

    /**
     * Get API key for service
     */
    protected function getApiKey(string $service): string
    {
        return Cache::remember("api_key_{$service}", 300, function () use ($service) {
            $config = config("services.{$service}");
            return $config['api_key'] ?? '';
        });
    }

    /**
     * Register new route
     */
    public function registerRoute(array $routeData): ApiRoute
    {
        $route = ApiRoute::create($routeData);
        
        // Clear cache
        Cache::forget('api_gateway_routes');
        
        // Reload routes
        $this->loadRoutes();
        
        return $route;
    }

    /**
     * Update route configuration
     */
    public function updateRoute(string $path, array $updates): bool
    {
        $result = ApiRoute::where('path', $path)->update($updates);
        
        if ($result) {
            Cache::forget('api_gateway_routes');
            $this->loadRoutes();
        }
        
        return $result > 0;
    }

    /**
     * Get gateway statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_routes' => count($this->routes),
            'load_balanced_routes' => count($this->loadBalancedRoutes),
            'circuit_breaker_status' => $this->circuitBreaker->getStatus(),
            'cache_stats' => [
                'routes_cache_hits' => Cache::get('api_gateway_routes_hits', 0),
                'routes_cache_misses' => Cache::get('api_gateway_routes_misses', 0),
            ],
        ];
    }

    /**
     * Clear all caches
     */
    public function clearCaches(): void
    {
        Cache::forget('api_gateway_routes');
        Cache::forget('api_gateway_load_balanced');
        Cache::forget('api_gateway_routes_hits');
        Cache::forget('api_gateway_routes_misses');
    }
}