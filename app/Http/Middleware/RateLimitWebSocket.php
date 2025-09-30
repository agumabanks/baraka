<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitWebSocket
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'websocket:' . $request->user()?->id ?: $request->ip();

        $limiter = RateLimiter::fixedWindow($key, 100); // 100 connections per minute

        if ($limiter->tooManyAttempts($key, 100)) {
            return response()->json([
                'error' => 'Too many WebSocket connection attempts. Please try again later.',
            ], 429);
        }

        $limiter->hit($key);

        return $next($request);
    }
}