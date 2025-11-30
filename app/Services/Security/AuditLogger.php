<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Log an account-related action
     *
     * @param User|int|null $user
     * @param string $action
     * @param array $changes Before/after values
     * @param array $metadata Additional context
     * @return void
     */
    public function log($user, string $action, array $changes = [], array $metadata = []): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $request = request();
        
        DB::table('account_audit_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'changes' => !empty($changes) ? json_encode($this->sanitizeChanges($changes)) : null,
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'performed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log a login attempt
     */
    public function logLogin(User $user, bool $success = true, array $metadata = []): void
    {
        $this->log(
            $user,
            $success ? 'login_success' : 'login_failed',
            [],
            array_merge([
                'success' => $success,
                'device' => $this->getDeviceInfo(),
            ], $metadata)
        );
    }

    /**
     * Log a logout
     */
    public function logLogout(User $user): void
    {
        $this->log($user, 'logout', [], [
            'device' => $this->getDeviceInfo(),
        ]);
    }

    /**
     * Log a password change
     */
    public function logPasswordChange(User $user, array $metadata = []): void
    {
        $this->log($user, 'password_changed', [], array_merge([
            'device' => $this->getDeviceInfo(),
            'forced' => $user->force_password_change ?? false,
        ], $metadata));
    }

    /**
     * Log email change
     */
    public function logEmailChange(User $user, string $oldEmail, string $newEmail): void
    {
        $this->log($user, 'email_changed', [
            'old_value' => $oldEmail,
            'new_value' => $newEmail,
        ]);
    }

    /**
     * Log 2FA enable/disable
     */
    public function log2FAChange(User $user, bool $enabled): void
    {
        $this->log($user, $enabled ? '2fa_enabled' : '2fa_disabled', [], [
            'device' => $this->getDeviceInfo(),
        ]);
    }

    /**
     * Log profile update
     */
    public function logProfileUpdate(User $user, array $changes): void
    {
        $this->log($user, 'profile_updated', $changes);
    }

    /**
     * Log session revocation
     */
    public function logSessionRevoked(User $user, string $sessionId, bool $revokedAll = false): void
    {
        $this->log($user, $revokedAll ? 'all_sessions_revoked' : 'session_revoked', [], [
            'session_id' => $sessionId,
            'revoked_all' => $revokedAll,
        ]);
    }

    /**
     * Log notification preference change
     */
    public function logNotificationPreferenceChange(User $user, array $changes): void
    {
        $this->log($user, 'notification_preferences_changed', $changes);
    }

    /**
     * Log account lockout
     */
    public function logAccountLocked(User $user, int $attempts, string $reason = 'Failed login attempts'): void
    {
        $this->log($user, 'account_locked', [], [
            'failed_attempts' => $attempts,
            'reason' => $reason,
            'locked_until' => $user->locked_until,
        ]);
    }

    /**
     * Log account unlock
     */
    public function logAccountUnlocked(User $user, User $unlockedBy = null): void
    {
        $this->log($user, 'account_unlocked', [], [
            'unlocked_by' => $unlockedBy ? $unlockedBy->id : 'auto',
            'unlocked_by_name' => $unlockedBy ? $unlockedBy->name : 'System',
        ]);
    }

    /**
     * Query audit logs with filters
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function query(array $filters = [])
    {
        $query = DB::table('account_audit_logs')
            ->orderBy('performed_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['from_date'])) {
            $query->where('performed_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('performed_at', '<=', $filters['to_date']);
        }

        return $query->get();
    }

    /**
     * Get recent activity for a user
     */
    public function getRecentActivity(User $user, int $limit = 10)
    {
        return DB::table('account_audit_logs')
            ->where('user_id', $user->id)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Sanitize changes to remove sensitive data
     *
     * @param array $changes
     * @return array
     */
    protected function sanitizeChanges(array $changes): array
    {
        $sensitiveFields = ['password', 'password_hash', 'remember_token', '2fa_secret', 'backup_codes'];
        
        foreach ($changes as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $changes[$key] = '[REDACTED]';
            }
        }
        
        return $changes;
    }

    /**
     * Get device information from request
     */
    protected function getDeviceInfo(): array
    {
        $request = request();
        $agent = $request->userAgent();
        
        // Basic device detection
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad/', $agent);
        $isTablet = preg_match('/iPad|Android(?!.*Mobile)/', $agent);
        
        $deviceType = $isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop');
        
        // Extract browser info
        $browser = 'Unknown';
        if (preg_match('/Firefox\/([\d.]+)/', $agent, $matches)) {
            $browser = 'Firefox ' . $matches[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/', $agent, $matches)) {
            $browser = 'Chrome ' . $matches[1];
        } elseif (preg_match('/Safari\/([\d.]+)/', $agent, $matches)) {
            $browser = 'Safari ' . $matches[1];
        } elseif (preg_match('/MSIE ([\d.]+)/', $agent, $matches)) {
            $browser = 'IE ' . $matches[1];
        }
        
        return [
            'type' => $deviceType,
            'browser' => $browser,
            'user_agent' => $agent,
        ];
    }

    /**
     * Cleanup old audit logs based on retention policy
     */
    public function cleanup(int $retentionDays = 730): int
    {
        return DB::table('account_audit_logs')
            ->where('performed_at', '<', now()->subDays($retentionDays))
            ->delete();
    }
}
