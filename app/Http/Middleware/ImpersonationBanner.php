<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

class ImpersonationBanner
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (session()->has('impersonator_id') && view()->exists('backend.partials.impersonation_banner')) {
            // Share a flag with all views to render a banner
            view()->share('isImpersonating', true);
        }

        return $response;
    }
}

