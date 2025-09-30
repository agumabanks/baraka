<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        $admin = $request->user();
        // Authorize via role
        if (! $admin->hasRole(['hq_admin', 'support', 'admin'])) {
            abort(403);
        }

        // Prevent nested impersonation
        if (session()->has('impersonator_id')) {
            return back()->with('error', 'Already impersonating another user. Please stop first.');
        }

        session([
            'impersonator_id' => $admin->id,
            'impersonation_started_at' => now()->toDateTimeString(),
        ]);

        // Log start
        DB::table('impersonation_logs')->insert([
            'admin_id' => $admin->id,
            'impersonated_user_id' => $user->id,
            'reason' => $request->input('reason'),
            'status' => 'started',
            'started_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('portal.index')->with('success', 'You are now impersonating '.$user->name);
    }

    public function stop(Request $request)
    {
        $impersonatorId = session('impersonator_id');
        if (! $impersonatorId) {
            return back()->with('error', 'No impersonation session active.');
        }

        $admin = User::find($impersonatorId);
        // Log stop
        DB::table('impersonation_logs')
            ->where('admin_id', $impersonatorId)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update(['status' => 'stopped', 'ended_at' => now(), 'updated_at' => now()]);

        session()->forget(['impersonator_id', 'impersonation_started_at']);

        if ($admin) {
            Auth::login($admin);

            return redirect()->route('admin.customers.index')->with('success', 'Impersonation ended.');
        }

        Auth::logout();

        return redirect()->route('login');
    }
}
