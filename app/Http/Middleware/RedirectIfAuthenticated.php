<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // Branch users go to branch dashboard
                if (method_exists($user, 'hasRole') && $user->hasRole(['branch_manager', 'branch_ops_manager', 'operations_admin'])) {
                    return redirect()->route('branch.dashboard');
                }

                if (method_exists($user, 'hasPermission') && $user->hasPermission(['branch_manage', 'branch_read'])) {
                    return redirect()->route('branch.dashboard');
                }

                // Admin and super-admin users go to the admin Blade dashboard
                if (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
                    return redirect()->route('admin.dashboard');
                }

                // Default fallback to HOME
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
