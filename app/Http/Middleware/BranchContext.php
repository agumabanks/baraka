<?php

namespace App\Http\Middleware;

use App\Models\Backend\Branch;
use Closure;
use Illuminate\Http\Request;

class BranchContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Determine branch for the user. Admin/regional roles can switch via ?branch_id.
        $branchId = null;

        if ($request->has('branch_id') && $user->hasRole(['admin', 'super-admin', 'operations_admin'])) {
            $branchId = (int) $request->query('branch_id');
            $request->session()->put('branch_context_id', $branchId);
        } elseif ($request->session()->has('branch_context_id')) {
            $branchId = (int) $request->session()->get('branch_context_id');
        } elseif ($user->primary_branch_id) {
            $branchId = (int) $user->primary_branch_id;
        } elseif ($user->relationLoaded('branchManager') || method_exists($user, 'branchManager')) {
            $branchId = (int) optional($user->branchManager)->branch_id;
        } elseif ($user->relationLoaded('branchWorker') || method_exists($user, 'branchWorker')) {
            $branchId = (int) optional($user->branchWorker)->branch_id;
        }

        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                // Share branch context for downstream use.
                $request->attributes->set('branch', $branch);
                view()->share('branchContext', $branch);
            } else {
                // Clear invalid session override
                $request->session()->forget('branch_context_id');
            }
        }

        return $next($request);
    }
}
