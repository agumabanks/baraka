<?php

namespace App\Services\Security;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * GdprComplianceService
 * 
 * GDPR compliance tools:
 * - Data export (right to portability)
 * - Data deletion (right to erasure)
 * - Consent management
 * - Data retention policies
 */
class GdprComplianceService
{
    protected DataEncryptionService $encryption;

    public function __construct(DataEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Export all user data (GDPR Article 20 - Right to data portability)
     */
    public function exportUserData(User $user): array
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'activity' => $this->getUserActivity($user),
            'shipments' => $this->getUserShipments($user),
            'sessions' => $this->getUserSessions($user),
        ];

        return $data;
    }

    /**
     * Export customer data
     */
    public function exportCustomerData(Customer $customer): array
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'created_at' => $customer->created_at?->toIso8601String(),
            ],
            'shipments' => $this->getCustomerShipments($customer),
            'invoices' => $this->getCustomerInvoices($customer),
        ];

        return $data;
    }

    /**
     * Delete user data (GDPR Article 17 - Right to erasure)
     */
    public function deleteUserData(User $user, bool $hardDelete = false): array
    {
        $deleted = [];

        DB::transaction(function () use ($user, $hardDelete, &$deleted) {
            // Anonymize or delete related records
            if ($hardDelete) {
                // Hard delete - remove all data
                DB::table('login_sessions')->where('user_id', $user->id)->delete();
                DB::table('account_audit_logs')->where('user_id', $user->id)->delete();
                DB::table('api_keys')->where('user_id', $user->id)->delete();
                DB::table('webhook_subscriptions')->where('user_id', $user->id)->delete();
                
                $deleted['hard_deleted'] = true;
            } else {
                // Soft delete - anonymize PII
                $anonymizedName = 'Deleted User ' . $user->id;
                $anonymizedEmail = "deleted_{$user->id}@anonymized.local";

                $user->update([
                    'name' => $anonymizedName,
                    'email' => $anonymizedEmail,
                    'phone' => null,
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                    'deleted_at' => now(),
                ]);

                $deleted['anonymized'] = true;
            }

            $deleted['user_id'] = $user->id;
            $deleted['deleted_at'] = now()->toIso8601String();
        });

        return $deleted;
    }

    /**
     * Delete customer data
     */
    public function deleteCustomerData(Customer $customer, bool $hardDelete = false): array
    {
        $deleted = [];

        DB::transaction(function () use ($customer, $hardDelete, &$deleted) {
            if ($hardDelete) {
                // Check for active shipments
                $activeShipments = $customer->shipments()
                    ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                    ->count();

                if ($activeShipments > 0) {
                    throw new \Exception('Cannot delete customer with active shipments');
                }

                $deleted['hard_deleted'] = true;
            } else {
                // Anonymize
                $customer->update([
                    'name' => 'Deleted Customer ' . $customer->id,
                    'email' => "deleted_{$customer->id}@anonymized.local",
                    'phone' => null,
                    'address' => null,
                    'deleted_at' => now(),
                ]);

                $deleted['anonymized'] = true;
            }

            $deleted['customer_id'] = $customer->id;
            $deleted['deleted_at'] = now()->toIso8601String();
        });

        return $deleted;
    }

    /**
     * Get data retention report
     */
    public function getDataRetentionReport(): array
    {
        $retentionPeriods = [
            'shipments' => 7 * 365, // 7 years (financial records)
            'invoices' => 7 * 365,
            'audit_logs' => 2 * 365, // 2 years
            'api_logs' => 90, // 90 days
            'sessions' => 30, // 30 days
        ];

        $report = [];

        foreach ($retentionPeriods as $type => $days) {
            $cutoffDate = now()->subDays($days);
            
            $count = match ($type) {
                'shipments' => DB::table('shipments')->where('created_at', '<', $cutoffDate)->count(),
                'invoices' => DB::table('invoices')->where('created_at', '<', $cutoffDate)->count(),
                'audit_logs' => DB::table('account_audit_logs')->where('created_at', '<', $cutoffDate)->count(),
                'api_logs' => DB::table('api_request_logs')->where('created_at', '<', $cutoffDate)->count(),
                'sessions' => DB::table('login_sessions')->where('created_at', '<', $cutoffDate)->count(),
                default => 0,
            };

            $report[$type] = [
                'retention_days' => $days,
                'cutoff_date' => $cutoffDate->toDateString(),
                'records_past_retention' => $count,
            ];
        }

        return $report;
    }

    /**
     * Purge data past retention period
     */
    public function purgeExpiredData(): array
    {
        $purged = [];

        // API logs - 90 days
        $purged['api_logs'] = DB::table('api_request_logs')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        // Sessions - 30 days
        $purged['sessions'] = DB::table('login_sessions')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        // Webhook deliveries - 30 days
        $purged['webhook_deliveries'] = DB::table('webhook_deliveries')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        // Notification logs - 90 days
        $purged['notification_logs'] = DB::table('notification_logs')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        $purged['purged_at'] = now()->toIso8601String();

        return $purged;
    }

    /**
     * Get user activity for export
     */
    protected function getUserActivity(User $user): array
    {
        return DB::table('account_audit_logs')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get(['action', 'ip_address', 'created_at'])
            ->toArray();
    }

    /**
     * Get user shipments for export
     */
    protected function getUserShipments(User $user): array
    {
        return DB::table('shipments')
            ->where('created_by', $user->id)
            ->orWhere('assigned_driver_id', $user->id)
            ->select(['tracking_number', 'status', 'created_at', 'delivered_at'])
            ->limit(1000)
            ->get()
            ->toArray();
    }

    /**
     * Get user sessions for export
     */
    protected function getUserSessions(User $user): array
    {
        return DB::table('login_sessions')
            ->where('user_id', $user->id)
            ->select(['ip_address', 'user_agent', 'created_at', 'last_activity_at'])
            ->limit(100)
            ->get()
            ->toArray();
    }

    /**
     * Get customer shipments for export
     */
    protected function getCustomerShipments(Customer $customer): array
    {
        return $customer->shipments()
            ->select(['tracking_number', 'status', 'origin_branch_id', 'dest_branch_id', 'created_at'])
            ->limit(1000)
            ->get()
            ->toArray();
    }

    /**
     * Get customer invoices for export
     */
    protected function getCustomerInvoices(Customer $customer): array
    {
        return DB::table('invoices')
            ->where('customer_id', $customer->id)
            ->select(['invoice_number', 'total_amount', 'status', 'created_at'])
            ->limit(500)
            ->get()
            ->toArray();
    }
}
