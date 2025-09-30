<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        if (Auth::check()) {
            // Super Admin bypass: allow all permissions
            if (method_exists(Auth::user(), 'hasRole') && Auth::user()->hasRole('super-admin')) {
                return $next($request);
            }

            // Explicit permission check
            if (in_array($permission, Auth::user()->permissions ?? [], true)) {
                return $next($request);
            }
        }

        return redirect('/');
        abort('403');
    }
}
