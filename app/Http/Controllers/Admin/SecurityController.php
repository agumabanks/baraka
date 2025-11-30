<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Security\DataEncryptionService;
use App\Services\Security\GdprComplianceService;
use App\Services\Security\AuditLogger;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    protected GdprComplianceService $gdprService;
    protected AuditLogger $auditLogger;

    public function __construct(GdprComplianceService $gdprService, AuditLogger $auditLogger)
    {
        $this->gdprService = $gdprService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Security dashboard
     */
    public function dashboard(Request $request)
    {
        return view('admin.security.dashboard');
    }

    /**
     * Get security overview data
     */
    public function getOverview(Request $request): JsonResponse
    {
        $last24Hours = now()->subDay();

        $data = [
            'login_attempts' => [
                'successful' => DB::table('account_audit_logs')
                    ->where('action', 'login_success')
                    ->where('created_at', '>=', $last24Hours)
                    ->count(),
                'failed' => DB::table('account_audit_logs')
                    ->where('action', 'login_failed')
                    ->where('created_at', '>=', $last24Hours)
                    ->count(),
            ],
            'active_sessions' => DB::table('login_sessions')
                ->where('last_activity_at', '>=', now()->subMinutes(30))
                ->count(),
            'api_requests' => DB::table('api_request_logs')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'locked_accounts' => User::where('is_locked', true)->count(),
            'recent_security_events' => DB::table('account_audit_logs')
                ->whereIn('action', ['login_failed', 'password_changed', 'account_locked', 'suspicious_activity'])
                ->where('created_at', '>=', $last24Hours)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get audit logs
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        $query = DB::table('account_audit_logs')
            ->leftJoin('users', 'account_audit_logs.user_id', '=', 'users.id')
            ->select([
                'account_audit_logs.*',
                'users.name as user_name',
                'users.email as user_email',
            ]);

        if ($request->has('user_id')) {
            $query->where('account_audit_logs.user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('from')) {
            $query->where('account_audit_logs.created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('account_audit_logs.created_at', '<=', $request->to);
        }

        $logs = $query->orderByDesc('account_audit_logs.created_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Export user data (GDPR)
     */
    public function exportUserData(Request $request, User $user): JsonResponse
    {
        $this->auditLogger->log('gdpr_data_export', $user->id, [
            'requested_by' => auth()->id(),
        ]);

        $data = $this->gdprService->exportUserData($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export customer data (GDPR)
     */
    public function exportCustomerData(Request $request, Customer $customer): JsonResponse
    {
        $this->auditLogger->log('gdpr_data_export', null, [
            'customer_id' => $customer->id,
            'requested_by' => auth()->id(),
        ]);

        $data = $this->gdprService->exportCustomerData($customer);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Delete user data (GDPR - Right to erasure)
     */
    public function deleteUserData(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'hard_delete' => 'boolean',
            'confirmation' => 'required|string|in:DELETE',
        ]);

        $this->auditLogger->log('gdpr_data_deletion', $user->id, [
            'requested_by' => auth()->id(),
            'hard_delete' => $request->boolean('hard_delete'),
        ]);

        try {
            $result = $this->gdprService->deleteUserData($user, $request->boolean('hard_delete'));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get data retention report
     */
    public function getDataRetentionReport(): JsonResponse
    {
        $report = $this->gdprService->getDataRetentionReport();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Purge expired data
     */
    public function purgeExpiredData(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation' => 'required|string|in:PURGE',
        ]);

        $this->auditLogger->log('data_purge', null, [
            'requested_by' => auth()->id(),
        ]);

        $result = $this->gdprService->purgeExpiredData();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions(Request $request): JsonResponse
    {
        $sessions = DB::table('login_sessions')
            ->join('users', 'login_sessions.user_id', '=', 'users.id')
            ->where('login_sessions.last_activity_at', '>=', now()->subMinutes(30))
            ->select([
                'login_sessions.*',
                'users.name',
                'users.email',
            ])
            ->orderByDesc('login_sessions.last_activity_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Terminate session
     */
    public function terminateSession(Request $request, int $sessionId): JsonResponse
    {
        $session = DB::table('login_sessions')->where('id', $sessionId)->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $this->auditLogger->log('session_terminated', $session->user_id, [
            'terminated_by' => auth()->id(),
            'session_id' => $sessionId,
        ]);

        DB::table('login_sessions')->where('id', $sessionId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session terminated',
        ]);
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(Request $request, User $user): JsonResponse
    {
        $user->update([
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        $this->auditLogger->log('account_unlocked', $user->id, [
            'unlocked_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account unlocked',
        ]);
    }
}
