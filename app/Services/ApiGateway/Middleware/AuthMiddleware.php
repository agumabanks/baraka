<?php

namespace App\Services\ApiGateway\Middleware;

use App\Services\ApiGateway\ApiGatewayContext;
use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authentication middleware for API Gateway
 */
class AuthMiddleware implements MiddlewareInterface
{
    protected $next;

    /**
     * Process the request through the middleware
     */
    public function handle(ApiGatewayContext $context): bool
    {
        if (!$this->shouldExecute($context)) {
            return $this->getNext() ? $this->getNext()->handle($context) : true;
        }

        $request = $context->getRequest();
        $route = $context->getRoute();

        try {
            // Get authentication configuration
            $authConfig = $this->getAuthConfig($route);
            
            if ($authConfig['required']) {
                $authResult = $this->authenticate($request, $authConfig);
                
                if (!$authResult['success']) {
                    return $this->handleAuthenticationFailed($context, $authResult);
                }

                // Set authenticated user in context
                $context->setUser($authResult['user']);
                $context->setData('auth_config', $authConfig);
                $context->setData('auth_provider', $authResult['provider']);
            }

            // Check authorization if required
            if ($this->requiresAuthorization($authConfig)) {
                $authResult = $this->authorize($context, $authConfig);
                
                if (!$authResult['authorized']) {
                    return $this->handleAuthorizationFailed($context, $authResult);
                }
            }

            // Continue to next middleware
            return $this->getNext() ? $this->getNext()->handle($context) : true;

        } catch (\Exception $e) {
            $context->log('error', 'Authentication middleware error', [
                'error' => $e->getMessage(),
                'route' => $route['path'] ?? 'unknown',
            ]);

            return $this->handleAuthenticationError($context, $e);
        }
    }

    /**
     * Get authentication configuration for the route
     */
    protected function getAuthConfig(array $route): array
    {
        $config = [
            'required' => false,
            'providers' => ['api_key', 'jwt', 'bearer'],
            'authorize' => false,
            'permissions' => [],
            'roles' => [],
            'scopes' => [],
        ];

        // Get route-specific configuration
        if (isset($route['auth_config'])) {
            $routeConfig = $route['auth_config'];
            
            if (is_array($routeConfig)) {
                $config = array_merge($config, $routeConfig);
            }
        }

        return $config;
    }

    /**
     * Authenticate request using configured providers
     */
    protected function authenticate(Request $request, array $config): array
    {
        $providers = $config['providers'];
        
        foreach ($providers as $provider) {
            $result = $this->authenticateWithProvider($request, $provider);
            
            if ($result['success']) {
                return $result;
            }
        }

        return [
            'success' => false,
            'error' => 'Authentication failed for all configured providers',
            'providers_tried' => $providers,
        ];
    }

    /**
     * Authenticate with specific provider
     */
    protected function authenticateWithProvider(Request $request, string $provider): array
    {
        switch ($provider) {
            case 'api_key':
                return $this->authenticateWithApiKey($request);
            case 'jwt':
                return $this->authenticateWithJWT($request);
            case 'bearer':
                return $this->authenticateWithBearer($request);
            case 'oauth2':
                return $this->authenticateWithOAuth2($request);
            default:
                return ['success' => false, 'error' => "Unknown auth provider: {$provider}"];
        }
    }

    /**
     * Authenticate using API key
     */
    protected function authenticateWithApiKey(Request $request): array
    {
        $apiKey = $request->header('X-API-Key') ?: $request->input('api_key');
        
        if (!$apiKey) {
            return ['success' => false, 'error' => 'API key not provided'];
        }

        // Check cache first
        $cacheKey = "api_key_auth:{$apiKey}";
        $cachedAuth = Cache::get($cacheKey);
        
        if ($cachedAuth) {
            return $cachedAuth;
        }

        // Validate API key
        $apiKeyRecord = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$apiKeyRecord) {
            return ['success' => false, 'error' => 'Invalid or expired API key'];
        }

        // Get associated user
        $user = $apiKeyRecord->user;
        
        if (!$user) {
            return ['success' => false, 'error' => 'API key user not found'];
        }

        // Update last used timestamp
        $apiKeyRecord->update(['last_used_at' => now()]);

        $result = [
            'success' => true,
            'user' => $user,
            'api_key' => $apiKeyRecord,
            'provider' => 'api_key',
            'permissions' => $apiKeyRecord->permissions,
            'rate_limit' => $apiKeyRecord->rate_limit,
        ];

        // Cache the result
        Cache::put($cacheKey, $result, 300); // 5 minutes

        return $result;
    }

    /**
     * Authenticate using JWT token
     */
    protected function authenticateWithJWT(Request $request): array
    {
        $token = $this->extractJWTToken($request);
        
        if (!$token) {
            return ['success' => false, 'error' => 'JWT token not provided'];
        }

        try {
            $decoded = JWT::decode($token, new Key(config('app.jwt_secret'), 'HS256'));
            
            $user = User::find($decoded->sub);
            
            if (!$user) {
                return ['success' => false, 'error' => 'JWT user not found'];
            }

            return [
                'success' => true,
                'user' => $user,
                'provider' => 'jwt',
                'decoded_token' => $decoded,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Invalid JWT token: ' . $e->getMessage()];
        }
    }

    /**
     * Authenticate using Bearer token
     */
    protected function authenticateWithBearer(Request $request): array
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return ['success' => false, 'error' => 'Bearer token not provided'];
        }

        // Check if it's a Laravel Sanctum token
        if (strlen($token) === 40) {
            return $this->authenticateWithSanctumToken($token);
        }

        // Otherwise, try as JWT
        return $this->authenticateWithJWT($request);
    }

    /**
     * Authenticate using Laravel Sanctum token
     */
    protected function authenticateWithSanctumToken(string $token): array
    {
        $personalAccessToken = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $token))
            ->first();

        if (!$personalAccessToken) {
            return ['success' => false, 'error' => 'Invalid Sanctum token'];
        }

        if ($personalAccessToken->expires_at && now()->greaterThan($personalAccessToken->expires_at)) {
            return ['success' => false, 'error' => 'Token has expired'];
        }

        $user = User::find($personalAccessToken->tokenable_id);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Token user not found'];
        }

        // Update last used timestamp
        DB::table('personal_access_tokens')
            ->where('id', $personalAccessToken->id)
            ->update(['last_used_at' => now()]);

        return [
            'success' => true,
            'user' => $user,
            'provider' => 'sanctum',
            'token' => $personalAccessToken,
        ];
    }

    /**
     * Authenticate using OAuth2
     */
    protected function authenticateWithOAuth2(Request $request): array
    {
        // OAuth2 implementation would go here
        // This is a placeholder for future OAuth2 integration
        
        return ['success' => false, 'error' => 'OAuth2 authentication not implemented yet'];
    }

    /**
     * Extract JWT token from request
     */
    protected function extractJWTToken(Request $request): ?string
    {
        // First try Authorization header
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Fall back to query parameter
        return $request->query('token');
    }

    /**
     * Check if authorization is required
     */
    protected function requiresAuthorization(array $authConfig): bool
    {
        return $authConfig['authorize'] === true || 
               !empty($authConfig['permissions']) || 
               !empty($authConfig['roles']) || 
               !empty($authConfig['scopes']);
    }

    /**
     * Authorize authenticated user
     */
    protected function authorize(ApiGatewayContext $context, array $config): array
    {
        $user = $context->getUser();
        
        if (!$user) {
            return ['authorized' => false, 'error' => 'No authenticated user found'];
        }

        // Check permissions
        if (!empty($config['permissions'])) {
            foreach ($config['permissions'] as $permission) {
                if (!$user->can($permission)) {
                    return ['authorized' => false, 'error' => "Missing permission: {$permission}"];
                }
            }
        }

        // Check roles
        if (!empty($config['roles'])) {
            $userRoles = $user->roles->pluck('name')->toArray();
            $hasRequiredRole = !empty(array_intersect($userRoles, $config['roles']));
            
            if (!$hasRequiredRole) {
                return ['authorized' => false, 'error' => 'Insufficient role privileges'];
            }
        }

        // Check scopes (if using API key with scopes)
        if (!empty($config['scopes'])) {
            $userScopes = $context->getData('auth_config')['permissions'] ?? [];
            $hasRequiredScope = !empty(array_intersect($userScopes, $config['scopes']));
            
            if (!$hasRequiredScope) {
                return ['authorized' => false, 'error' => 'Insufficient scope privileges'];
            }
        }

        return ['authorized' => true];
    }

    /**
     * Handle authentication failure
     */
    protected function handleAuthenticationFailed(ApiGatewayContext $context, array $authResult): Response
    {
        $context->log('warning', 'Authentication failed', [
            'error' => $authResult['error'],
            'providers_tried' => $authResult['providers_tried'] ?? [],
            'client_ip' => $context->getMetadata('client_ip'),
        ]);

        return $context->createErrorResponse(
            'Authentication required',
            'AUTHENTICATION_REQUIRED',
            401,
            ['error' => $authResult['error']]
        );
    }

    /**
     * Handle authorization failure
     */
    protected function handleAuthorizationFailed(ApiGatewayContext $context, array $authResult): Response
    {
        $context->log('warning', 'Authorization failed', [
            'error' => $authResult['error'],
            'user_id' => $context->getUser()->id ?? 'unknown',
        ]);

        return $context->createErrorResponse(
            'Insufficient permissions',
            'AUTHORIZATION_FAILED',
            403,
            ['error' => $authResult['error']]
        );
    }

    /**
     * Handle authentication error
     */
    protected function handleAuthenticationError(ApiGatewayContext $context, \Exception $e): Response
    {
        $context->log('error', 'Authentication error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $context->createErrorResponse(
            'Authentication service unavailable',
            'AUTH_SERVICE_ERROR',
            503
        );
    }

    /**
     * Set the next middleware in the chain
     */
    public function setNext(MiddlewareInterface $next): self
    {
        $this->next = $next;
        return $this;
    }

    /**
     * Get the next middleware in the chain
     */
    public function getNext(): ?MiddlewareInterface
    {
        return $this->next;
    }

    /**
     * Get middleware priority
     */
    public function getPriority(): int
    {
        return 20; // Second priority after rate limiting
    }

    /**
     * Check if middleware should be executed for this request
     */
    public function shouldExecute(ApiGatewayContext $context): bool
    {
        $route = $context->getRoute();
        
        // Skip authentication for certain routes
        $skipPaths = config('api_gateway.skip_auth_paths', [
            '/health',
            '/status',
            '/ping',
            '/public',
        ]);
        
        $path = $context->getRouteParam('path', '');
        
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get middleware name
     */
    public function getName(): string
    {
        return 'AuthMiddleware';
    }
}