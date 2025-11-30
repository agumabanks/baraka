<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Security\MfaService;
use App\Services\Security\AuditLogger;
use App\Models\Security\SecurityMfaDevice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MfaController extends Controller
{
    protected MfaService $mfaService;
    protected AuditLogger $auditLogger;

    public function __construct(MfaService $mfaService, AuditLogger $auditLogger)
    {
        $this->mfaService = $mfaService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * MFA settings page
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $devices = $this->mfaService->getUserDevices($user);
        $hasMfa = $this->mfaService->hasMfaEnabled($user);

        return view('admin.security.mfa', compact('devices', 'hasMfa'));
    }

    /**
     * Generate TOTP setup data
     */
    public function generateTotp(Request $request): JsonResponse
    {
        $user = $request->user();
        $secret = $this->mfaService->generateTotpSecret();

        // Store temporarily in session
        session(['mfa_totp_secret' => $secret]);

        // Generate QR code URI
        $appName = config('app.name', 'Baraka');
        $otpauthUrl = "otpauth://totp/{$appName}:{$user->email}?secret={$secret}&issuer={$appName}&algorithm=SHA1&digits=6&period=30";

        // Generate QR code as base64
        $qrCode = null;
        if (class_exists(QrCode::class)) {
            try {
                $qrCode = base64_encode(QrCode::format('png')->size(200)->generate($otpauthUrl));
            } catch (\Exception $e) {
                // QR code generation failed, user can manually enter secret
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code' => $qrCode ? "data:image/png;base64,{$qrCode}" : null,
                'manual_entry_key' => chunk_split($secret, 4, ' '),
            ],
        ]);
    }

    /**
     * Verify and enable TOTP
     */
    public function enableTotp(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $secret = session('mfa_totp_secret');

        if (!$secret) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please regenerate the QR code.',
            ], 400);
        }

        // Verify the code
        if (!$this->mfaService->verifyTotpCode($secret, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please try again.',
            ], 400);
        }

        // Register the device
        $device = $this->mfaService->registerDevice(
            $user,
            $request->device_name ?? 'Authenticator App',
            'totp',
            'totp_' . $user->id,
            ['secret' => encrypt($secret)]
        );

        // Mark as verified
        $device->update(['is_verified' => true, 'verified_at' => now()]);

        // Clear session
        session()->forget('mfa_totp_secret');

        // Log the event
        $this->auditLogger->log2FAChange($user, true);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully.',
            'backup_codes' => $device->backup_codes,
        ]);
    }

    /**
     * Setup SMS MFA
     */
    public function setupSms(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
        ]);

        $user = $request->user();

        // Send verification code
        if (!$this->mfaService->sendSmsCode($user, $request->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
            ], 500);
        }

        // Store phone in session
        session(['mfa_sms_phone' => $request->phone]);

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your phone.',
        ]);
    }

    /**
     * Verify and enable SMS MFA
     */
    public function enableSms(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $phone = session('mfa_sms_phone');

        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please start setup again.',
            ], 400);
        }

        // Verify the code
        if (!$this->mfaService->verifyMfaCode($user, $request->code, 'sms', $phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
            ], 400);
        }

        // Register the device
        $device = $this->mfaService->registerDevice(
            $user,
            'SMS - ' . substr($phone, -4),
            'sms',
            $phone,
            ['phone' => $phone]
        );

        $device->update(['is_verified' => true, 'verified_at' => now()]);

        session()->forget('mfa_sms_phone');
        $this->auditLogger->log2FAChange($user, true);

        return response()->json([
            'success' => true,
            'message' => 'SMS two-factor authentication enabled.',
            'backup_codes' => $device->backup_codes,
        ]);
    }

    /**
     * Setup Email MFA
     */
    public function setupEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->mfaService->sendEmailCode($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to ' . $user->email,
        ]);
    }

    /**
     * Verify and enable Email MFA
     */
    public function enableEmail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$this->mfaService->verifyMfaCode($user, $request->code, 'email')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
            ], 400);
        }

        $device = $this->mfaService->registerDevice(
            $user,
            'Email - ' . $user->email,
            'email',
            $user->email,
            ['email' => $user->email]
        );

        $device->update(['is_verified' => true, 'verified_at' => now()]);
        $this->auditLogger->log2FAChange($user, true);

        return response()->json([
            'success' => true,
            'message' => 'Email two-factor authentication enabled.',
            'backup_codes' => $device->backup_codes,
        ]);
    }

    /**
     * Remove MFA device
     */
    public function removeDevice(Request $request, int $deviceId): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.',
            ], 400);
        }

        if (!$this->mfaService->removeDevice($user, $deviceId)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove device.',
            ], 400);
        }

        // Check if MFA is still enabled
        $hasMfa = $this->mfaService->hasMfaEnabled($user);
        if (!$hasMfa) {
            $this->auditLogger->log2FAChange($user, false);
        }

        return response()->json([
            'success' => true,
            'message' => 'MFA device removed.',
        ]);
    }

    /**
     * Set device as primary
     */
    public function setPrimary(Request $request, int $deviceId): JsonResponse
    {
        $user = $request->user();

        // Reset all devices
        SecurityMfaDevice::where('user_id', $user->id)->update(['is_primary' => false]);

        // Set new primary
        $device = SecurityMfaDevice::where('user_id', $user->id)->findOrFail($deviceId);
        $device->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary MFA device updated.',
        ]);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.',
            ], 400);
        }

        $primaryDevice = $this->mfaService->getPrimaryDevice($user);

        if (!$primaryDevice) {
            return response()->json([
                'success' => false,
                'message' => 'No MFA device configured.',
            ], 400);
        }

        $backupCodes = $primaryDevice->generateBackupCodes();
        $primaryDevice->update(['backup_codes' => $backupCodes]);

        $this->auditLogger->log($user, 'backup_codes_regenerated');

        return response()->json([
            'success' => true,
            'backup_codes' => $backupCodes,
        ]);
    }

    /**
     * Verify MFA during login (API endpoint)
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
            'method' => 'nullable|in:totp,sms,email,backup',
        ]);

        $user = \App\Models\User::find($request->user_id);
        $method = $request->method ?? 'totp';

        // Check lockout
        if ($this->mfaService->isLockedOut($user, $method)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please try again later.',
            ], 429);
        }

        // Handle backup code
        if ($method === 'backup') {
            $device = $this->mfaService->getPrimaryDevice($user);
            if ($device && $device->useBackupCode($request->code)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup code verified.',
                    'remaining_codes' => count($device->backup_codes ?? []),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid backup code.',
            ], 400);
        }

        // Handle TOTP
        if ($method === 'totp') {
            $device = SecurityMfaDevice::where('user_id', $user->id)
                ->where('device_type', 'totp')
                ->where('is_verified', true)
                ->first();

            if ($device) {
                $secret = decrypt($device->metadata['secret'] ?? '');
                if ($this->mfaService->verifyTotpCode($secret, $request->code)) {
                    $device->update(['last_used_at' => now()]);
                    return response()->json([
                        'success' => true,
                        'message' => 'MFA verified.',
                    ]);
                }
            }
        }

        // Handle SMS/Email
        if ($this->mfaService->verifyMfaCode($user, $request->code, $method)) {
            return response()->json([
                'success' => true,
                'message' => 'MFA verified.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid verification code.',
        ], 400);
    }

    /**
     * MFA Policy Settings Page (Admin Toggle)
     */
    public function policySettings(Request $request)
    {
        $this->authorize('manage', \App\Models\User::class);

        // Get current MFA policy settings
        $settings = [
            'mfa_enforcement' => config('account_security.mfa.enforcement', 'optional'),
            'mfa_grace_period_days' => config('account_security.mfa.grace_period_days', 7),
            'mfa_required_for_roles' => config('account_security.mfa.required_for_roles', ['admin', 'super-admin']),
            'mfa_allowed_methods' => config('account_security.mfa.allowed_methods', ['totp', 'sms', 'email']),
            'mfa_remember_device_days' => config('account_security.mfa.remember_device_days', 30),
        ];

        // Get MFA adoption statistics
        $stats = $this->getMfaStatistics();

        return view('admin.security.mfa-settings', compact('settings', 'stats'));
    }

    /**
     * Update MFA Policy Settings
     */
    public function updatePolicySettings(Request $request): JsonResponse
    {
        $this->authorize('manage', \App\Models\User::class);

        $validated = $request->validate([
            'mfa_enforcement' => 'required|in:disabled,optional,required,required_for_admins',
            'mfa_grace_period_days' => 'required|integer|min:0|max:30',
            'mfa_required_for_roles' => 'nullable|array',
            'mfa_required_for_roles.*' => 'string|in:admin,super-admin,branch_manager,operations_manager,finance_manager',
            'mfa_allowed_methods' => 'required|array|min:1',
            'mfa_allowed_methods.*' => 'string|in:totp,sms,email',
            'mfa_remember_device_days' => 'required|integer|min:0|max:90',
        ]);

        try {
            // Update settings in database or config cache
            \App\Support\SystemSettings::set('mfa.enforcement', $validated['mfa_enforcement']);
            \App\Support\SystemSettings::set('mfa.grace_period_days', $validated['mfa_grace_period_days']);
            \App\Support\SystemSettings::set('mfa.required_for_roles', $validated['mfa_required_for_roles'] ?? []);
            \App\Support\SystemSettings::set('mfa.allowed_methods', $validated['mfa_allowed_methods']);
            \App\Support\SystemSettings::set('mfa.remember_device_days', $validated['mfa_remember_device_days']);

            // Log the change
            $this->auditLogger->log('mfa_policy_updated', [
                'changes' => $validated,
                'updated_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'MFA policy settings updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update MFA settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get MFA adoption statistics
     */
    protected function getMfaStatistics(): array
    {
        $totalUsers = \App\Models\User::count();
        $usersWithMfa = \App\Models\User::whereHas('mfaDevices', function ($q) {
            $q->where('is_verified', true);
        })->count();

        $devicesByType = SecurityMfaDevice::where('is_verified', true)
            ->selectRaw('device_type, count(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        return [
            'total_users' => $totalUsers,
            'users_with_mfa' => $usersWithMfa,
            'adoption_rate' => $totalUsers > 0 ? round(($usersWithMfa / $totalUsers) * 100, 1) : 0,
            'devices_by_type' => $devicesByType,
            'totp_count' => $devicesByType['totp'] ?? 0,
            'sms_count' => $devicesByType['sms'] ?? 0,
            'email_count' => $devicesByType['email'] ?? 0,
        ];
    }
}
