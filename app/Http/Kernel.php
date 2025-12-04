<?php

namespace App\Http;

use App\Http\Middleware\CheckApiKeyMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\Cors::class,
        \App\Http\Middleware\SecurityHeaders::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\LanguageManager::class,
            \App\Http\Middleware\Security\TrackSessionActivity::class, // Track all session activity
            \App\Http\Middleware\Security\CheckAccountLockout::class,
            \App\Http\Middleware\Security\CheckPasswordExpiry::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // custom
        'hasPermission' => \App\Http\Middleware\PermissionCheckMiddleware::class,
        'XSS' => \App\Http\Middleware\XSS::class,
        'CheckApiKey' => CheckApiKeyMiddleware::class,
        'headersCheck' => \App\Http\Middleware\ModifyHeaderMiddleware::class,
        'IsInstalled' => \App\Http\Middleware\IsInstalledMiddleware::class,
        'IsNotInstalled' => \App\Http\Middleware\IsNotInstalledMiddleware::class,
        'impersonationBanner' => \App\Http\Middleware\ImpersonationBanner::class,
        'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
        'bind_device' => \App\Http\Middleware\BindDeviceMiddleware::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        // Accessibility and Audit Middleware
        'audit.logging' => \App\Http\Middleware\AuditLoggingMiddleware::class,
        'accessibility.validation' => \App\Http\Middleware\AccessibilityValidationMiddleware::class,
        'api.rate_limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
        'advanced-rate-limit' => \App\Http\Middleware\AdvancedRateLimitMiddleware::class,
        'api-security-validation' => \App\Http\Middleware\EnhancedApiSecurityMiddleware::class,
        'api.simple' => \App\Http\Middleware\SimpleApiMiddleware::class,
        'api.gateway' => \App\Http\Middleware\ApiGatewayMiddleware::class,
        'mobile.errors' => \App\Http\Middleware\MobileScanningErrorHandler::class,
        'branch.context' => \App\Http\Middleware\BranchContext::class,
        'api.key' => \App\Http\Middleware\ApiKeyAuthentication::class,
        'branch.locale' => \App\Http\Middleware\BranchLocale::class,
        'branch.isolation' => \App\Http\Middleware\EnforceBranchIsolation::class,
        'pos.enabled' => \App\Http\Middleware\EnsurePosEnabled::class,
        
        // Account Security Middleware
        'account.lockout' => \App\Http\Middleware\Security\CheckAccountLockout::class,
        'session.activity' => \App\Http\Middleware\Security\TrackSessionActivity::class,
        'password.expiry' => \App\Http\Middleware\Security\CheckPasswordExpiry::class,

    ];
}
