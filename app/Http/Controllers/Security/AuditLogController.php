<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AccountAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a paginated list of audit logs for admins.
     */
    public function index(Request $request)
    {
        $query = AccountAuditLog::query();
        if ($request->filled('user')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->input('user')}%");
            });
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('date')) {
            $query->whereDate('performed_at', $request->input('date'));
        }
        $logs = $query->orderByDesc('performed_at')->paginate(20);
        return view('branch.security.audit_logs', compact('logs'));
    }
}
