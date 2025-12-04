<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePosEnabled
{
    /**
    * Handle an incoming request.
    */
    public function handle(Request $request, Closure $next)
    {
        if (!config('pos.enhanced_enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
