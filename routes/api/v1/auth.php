<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::middleware(['api.prefix', 'api.throttle'])->group(function () {
    // Rate limiting: 5 attempts per minute for auth
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('v1.auth.login');

    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('v1.auth.register');

    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1')
        ->name('api.auth.forgot-password');

    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:3,1')
        ->name('api.auth.reset-password');

    // Public info endpoints
    Route::get('info', [AuthController::class, 'publicInfo'])
        ->name('api.auth.public-info');

    Route::get('languages', [AuthController::class, 'supportedLanguages'])
        ->name('api.auth.languages');
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    // Standard rate limiting: 60 requests per minute
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');

        Route::get('me', [AuthController::class, 'me'])
            ->name('api.auth.me');

        Route::put('profile', [AuthController::class, 'updateProfile'])
            ->name('api.auth.update-profile');

        Route::put('password', [AuthController::class, 'updatePassword'])
            ->name('api.auth.update-password');

        Route::get('session', [AuthController::class, 'sessionInfo'])
            ->name('api.auth.session-info');
    });

    // Token management (protected but less restrictive rate limiting)
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('tokens/refresh', [TokenController::class, 'refresh'])
            ->name('api.tokens.refresh');

        Route::get('tokens', [TokenController::class, 'index'])
            ->name('api.tokens.index');

        Route::delete('tokens/{token}', [TokenController::class, 'revoke'])
            ->name('api.tokens.revoke');

        Route::post('tokens/revoke-all', [TokenController::class, 'revokeAll'])
            ->name('api.tokens.revoke-all');
    });

    // Permission checking endpoints
    Route::get('permissions', [AuthController::class, 'getPermissions'])
        ->name('api.auth.permissions');

    Route::post('check-permissions', [AuthController::class, 'checkPermissions'])
        ->name('api.auth.check-permissions');

    // Two-factor authentication routes
    Route::post('2fa/enable', [AuthController::class, 'enable2FA'])
        ->name('api.auth.2fa.enable');

    Route::post('2fa/verify', [AuthController::class, 'verify2FA'])
        ->name('api.auth.2fa.verify');

    Route::post('2fa/disable', [AuthController::class, 'disable2FA'])
        ->name('api.auth.2fa.disable');

    Route::get('2fa/setup', [AuthController::class, 'setup2FA'])
        ->name('api.auth.2fa.setup');

    // Activity tracking
    Route::get('activity', [AuthController::class, 'getActivity'])
        ->name('api.auth.activity');

    Route::get('activity/audit-log', [AuthController::class, 'getAuditLog'])
        ->middleware('permission:audit_log_read')
        ->name('api.auth.audit-log');
});

// Webhook endpoints
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/auth/login-failed', [AuthController::class, 'loginFailedWebhook'])
        ->name('api.webhooks.auth.login-failed');

    Route::post('webhooks/auth/login-success', [AuthController::class, 'loginSuccessWebhook'])
        ->name('api.webhooks.auth.login-success');
});
