<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Support\BranchCache;
use App\Support\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BranchSettingsController extends Controller
{
    use ResolvesBranch;

    /**
     * Display branch settings dashboard
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $supportedLocales = translation_supported_languages();
        $settings = $branch->metadata['settings'] ?? [];
        $tab = $request->get('tab', 'general');

        // Get available timezones grouped by region
        $timezones = $this->getGroupedTimezones();

        return view('branch.settings', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'supportedLocales' => $supportedLocales,
            'systemLocale' => SystemSettings::defaultLocale(),
            'settings' => $settings,
            'activeTab' => $tab,
            'timezones' => $timezones,
            'currencies' => $this->getSupportedCurrencies(),
            'labelFormats' => $this->getLabelFormats(),
        ]);
    }

    /**
     * Update general settings
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $supportedLocales = translation_supported_languages();

        $payload = $request->validate([
            'preferred_language' => 'nullable|string|in:' . implode(',', $supportedLocales),
            'timezone' => 'nullable|string|max:120',
            'display_name' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:8',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|in:12,24',
        ]);

        $this->saveSettings($branch, 'general', $payload);

        Log::info('Branch general settings updated', [
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'General settings saved successfully.',
        ]);
    }

    /**
     * Update operations settings
     */
    public function updateOperations(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $payload = $request->validate([
            'operating_hours_start' => 'nullable|date_format:H:i',
            'operating_hours_end' => 'nullable|date_format:H:i',
            'cutoff_time' => 'nullable|date_format:H:i',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:0|max:6',
            'max_daily_shipments' => 'nullable|integer|min:0',
            'max_parcel_weight' => 'nullable|numeric|min:0',
            'sla_threshold' => 'nullable|integer|min:0|max:100',
            'auto_assign_drivers' => 'boolean',
            'require_pod' => 'boolean',
            'require_signature' => 'boolean',
            'enable_cod' => 'boolean',
            'cod_limit' => 'nullable|numeric|min:0',
            'operating_notes' => 'nullable|string|max:500',
        ]);

        $this->saveSettings($branch, 'operations', $payload);

        Log::info('Branch operations settings updated', [
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Operations settings saved successfully.',
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $payload = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'notify_new_shipment' => 'boolean',
            'notify_status_change' => 'boolean',
            'notify_delivery' => 'boolean',
            'notify_exception' => 'boolean',
            'notify_sla_breach' => 'boolean',
            'daily_summary' => 'boolean',
            'weekly_report' => 'boolean',
            'escalation_email' => 'nullable|email|max:255',
            'escalation_phone' => 'nullable|string|max:50',
        ]);

        $this->saveSettings($branch, 'notifications', $payload);

        Log::info('Branch notification settings updated', [
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings saved successfully.',
        ]);
    }

    /**
     * Update label/printing settings
     */
    public function updateLabels(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $payload = $request->validate([
            'label_format' => 'nullable|string|in:4x6,4x4,a4,thermal',
            'label_orientation' => 'nullable|string|in:portrait,landscape',
            'show_barcode' => 'boolean',
            'show_qr_code' => 'boolean',
            'show_logo' => 'boolean',
            'show_sender_address' => 'boolean',
            'show_weight' => 'boolean',
            'show_dimensions' => 'boolean',
            'show_cod_amount' => 'boolean',
            'show_special_instructions' => 'boolean',
            'copies_per_shipment' => 'nullable|integer|min:1|max:5',
            'auto_print' => 'boolean',
            'printer_name' => 'nullable|string|max:100',
        ]);

        $this->saveSettings($branch, 'labels', $payload);

        Log::info('Branch label settings updated', [
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Label settings saved successfully.',
        ]);
    }

    /**
     * Update security settings
     */
    public function updateSecurity(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $payload = $request->validate([
            'require_2fa' => 'boolean',
            'session_timeout' => 'nullable|integer|min:5|max:480',
            'ip_whitelist_enabled' => 'boolean',
            'ip_whitelist' => 'nullable|string|max:500',
            'audit_logging' => 'boolean',
            'data_retention_days' => 'nullable|integer|min:30|max:3650',
        ]);

        // Parse IP whitelist
        if (!empty($payload['ip_whitelist'])) {
            $payload['ip_whitelist'] = array_filter(
                array_map('trim', explode("\n", $payload['ip_whitelist']))
            );
        }

        $this->saveSettings($branch, 'security', $payload);

        Log::info('Branch security settings updated', [
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Security settings saved successfully.',
        ]);
    }

    /**
     * Legacy update method for backward compatibility
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $supportedLocales = translation_supported_languages();

        $payload = $request->validate([
            'preferred_language' => 'nullable|string|in:' . implode(',', $supportedLocales),
            'timezone' => 'nullable|string|max:120',
            'display_name' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email',
            'sla_threshold' => 'nullable|integer|min:0|max:100',
            'operating_notes' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:8',
        ]);

        $this->saveSettings($branch, 'general', $payload);

        return back()->with('success', 'Branch settings updated.');
    }

    /**
     * Save settings to branch metadata
     */
    protected function saveSettings($branch, string $section, array $payload): void
    {
        $metadata = $branch->metadata ?? [];
        $metadata['settings'] = $metadata['settings'] ?? [];
        $metadata['settings'][$section] = array_merge(
            $metadata['settings'][$section] ?? [],
            $payload
        );

        // Also save flat structure for backward compatibility
        if ($section === 'general') {
            $metadata['settings'] = array_merge($metadata['settings'], $payload);
        }

        if (Schema::hasColumn('branches', 'settings')) {
            $branch->settings = array_merge($branch->settings ?? [], $payload);
        }

        $branch->metadata = $metadata;
        $branch->save();

        BranchCache::flushForBranch($branch->id);
    }

    /**
     * Get settings for a specific section
     */
    protected function getSettings($branch, string $section): array
    {
        return $branch->metadata['settings'][$section] ?? [];
    }

    /**
     * Get grouped timezones
     */
    protected function getGroupedTimezones(): array
    {
        $timezones = [];
        $regions = [
            'Africa' => \DateTimeZone::AFRICA,
            'America' => \DateTimeZone::AMERICA,
            'Asia' => \DateTimeZone::ASIA,
            'Europe' => \DateTimeZone::EUROPE,
            'Pacific' => \DateTimeZone::PACIFIC,
        ];

        foreach ($regions as $name => $mask) {
            $timezones[$name] = \DateTimeZone::listIdentifiers($mask);
        }

        return $timezones;
    }

    /**
     * Get supported currencies
     */
    protected function getSupportedCurrencies(): array
    {
        return [
            'UGX' => 'Ugandan Shilling (UGX)',
            'KES' => 'Kenyan Shilling (KES)',
            'TZS' => 'Tanzanian Shilling (TZS)',
            'RWF' => 'Rwandan Franc (RWF)',
            'USD' => 'US Dollar (USD)',
            'EUR' => 'Euro (EUR)',
            'GBP' => 'British Pound (GBP)',
        ];
    }

    /**
     * Get label formats
     */
    protected function getLabelFormats(): array
    {
        return [
            '4x6' => '4" x 6" (Standard Shipping)',
            '4x4' => '4" x 4" (Square)',
            'a4' => 'A4 Paper',
            'thermal' => 'Thermal Roll (Receipt)',
        ];
    }
}
