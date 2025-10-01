<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Safe, non-invasive security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Build connect-src dynamically based on environment
        $connectSources = ["'self'", 'https:'];

        // Allow localhost connections in development
        if (config('app.env') !== 'production') {
            $connectSources[] = 'http://localhost:*';
            $connectSources[] = 'http://127.0.0.1:*';
        }

        // Allow additional hosts configured via environment variable (space separated)
        $extraConnectSources = array_filter(preg_split('/\s+/', (string) env('CSP_CONNECT_SRC', '')));
        if (! empty($extraConnectSources)) {
            $connectSources = array_merge($connectSources, $extraConnectSources);
        }

        $connectSrc = implode(' ', array_unique($connectSources));

        // Conservative CSP that won't break existing inline assets by default
        $csp = "default-src 'self' https: data:; " .
               "script-src 'self' 'unsafe-inline' https:; " .
               "style-src 'self' 'unsafe-inline' https:; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https: data:; " .
               "connect-src {$connectSrc}; " .
               "frame-ancestors 'none'";
        
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
