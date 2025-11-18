<?php

namespace App\Repositories\GeneralSettings;

use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeneralSettingsRepository implements GeneralSettingsInterface
{
    protected bool $supportsDetailsColumn;

    public function __construct()
    {
        $this->supportsDetailsColumn = Schema::hasColumn('general_settings', 'details');
    }

    public function all()
    {
        $row = GeneralSettings::query()->first();

        if (! $row) {
            $row = GeneralSettings::create([
                'name' => config('app.name', 'Baraka Sanaa'),
                'phone' => null,
                'tracking_id' => null,
                'details' => $this->defaultPreferences(),
                'prefix' => null,
            ]);

            $row->currency = $row->currency ?? 'UGX';
            $row->copyright = $row->copyright ?? sprintf('© %s', now()->year);
            $row->primary_color = $row->primary_color ?? '#1F2937';
            $row->text_color = $row->text_color ?? '#FFFFFF';
            $row->par_track_prefix = $row->par_track_prefix ?? 'BRK';
            $row->invoice_prefix = $row->invoice_prefix ?? 'INV';
            $row->save();
        }

        if ($this->supportsDetailsColumn) {
            $merged = $this->mergePreferences($row->details ?? []);
            if ($merged !== ($row->details ?? [])) {
                $row->details = $merged;
                $row->save();
            }

            return $row->fresh();
        }

        $storedPreferences = $this->readPreferencesFromDisk();
        $diskPreferences = $this->mergePreferences($storedPreferences);
        if ($diskPreferences !== $storedPreferences) {
            $this->writePreferencesToDisk($diskPreferences);
        }

        $fresh = $row->fresh();
        $fresh->setAttribute('details', $diskPreferences);

        return $fresh;
    }

    public function update($request)
    {
        $row = GeneralSettings::query()->first();

        if (! $row) {
            $row = GeneralSettings::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'tracking_id' => null,
                'details' => null,
                'prefix' => null,
            ]);
        }
        $row->name = $request->name;
        $row->phone = $request->phone;
        $row->email = $request->email;
        $row->address = $request->address;
        $row->currency = $request->currency;
        $row->copyright = $request->copyright;
        $row->par_track_prefix = Str::upper($request->par_track_prefix);
        $row->invoice_prefix = Str::upper($request->invoice_prefix);
        if ($request->primary_color) {
            $row->primary_color = $request->primary_color;
        }
        if ($request->text_color) {
            $row->text_color = $request->text_color;
        }

        $preferencesPayload = $request->input('preferences', []);
        if (is_string($preferencesPayload)) {
            $decoded = json_decode($preferencesPayload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $preferencesPayload = $decoded;
            } else {
                $preferencesPayload = [];
            }
        }

        if ($this->supportsDetailsColumn) {
            $row->details = $this->mergePreferences($row->details ?? [], $preferencesPayload);
        } else {
            $mergedPreferences = $this->mergePreferences($this->readPreferencesFromDisk(), $preferencesPayload);
            $this->writePreferencesToDisk($mergedPreferences);
        }

        if (isset($request->logo) && $request->logo != null) {
            $row->logo = $this->file($row->logo, $request->logo);
        }
        if (isset($request->light_logo) && $request->light_logo != null) {
            $row->light_logo = $this->file($row->light_logo, $request->light_logo);
        }
        if (isset($request->favicon) && $request->favicon != null) {
            $row->favicon = $this->file($row->favicon, $request->favicon);
        }
        $row->save();

        Cache::forget('settings');

        if (! $this->supportsDetailsColumn) {
            $fresh = $row->fresh();
            $fresh->setAttribute('details', $this->readPreferencesFromDisk());

            return $fresh;
        }

        return $row->fresh();

    }

    public function file($image_id, $image)
    {

        try {
            $image_name = '';
            if (! blank($image)) {
                $destinationPath = public_path('uploads/settings');
                $profileImage = date('YmdHis').random_int(1000, 9999).'.'.$image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name = 'uploads/settings/'.$profileImage;
            }
            if (blank($image_id)) {
                $upload = new Upload;
            } else {
                $upload = Upload::find($image_id);
                if (file_exists($upload->original)) {
                    unlink($upload->original);
                }
            }
            $upload->original = $image_name;
            $upload->save();

            return $upload->id;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function defaultPreferences(): array
    {
        return [
            'general' => [
                'tagline' => '',
                'support_email' => '',
                'timezone' => 'Africa/Nairobi',
                'country' => 'Uganda',
            ],
            'branding' => [
                'theme' => 'light',
                'sidebar_density' => 'comfortable',
                'enable_animations' => true,
            ],
            'operations' => [
                'auto_assign_drivers' => false,
                'enable_capacity_management' => false,
                'require_dispatch_approval' => true,
                'auto_generate_tracking_ids' => true,
                'enforce_pod_otp' => true,
                'allow_public_tracking' => true,
            ],
            'finance' => [
                'auto_reconcile' => false,
                'enforce_cod_settlement_workflow' => true,
                'enable_invoice_emails' => true,
                'default_tax_rate' => 0,
                'rounding_mode' => 'nearest',
            ],
            'notifications' => [
                'email' => true,
                'sms' => false,
                'push' => true,
                'daily_digest' => true,
                'escalate_incidents' => false,
            ],
            'integrations' => [
                'webhooks_enabled' => true,
                'webhooks_url' => '',
                'slack_enabled' => false,
                'slack_channel' => '',
                'power_bi_enabled' => false,
                'zapier_enabled' => false,
                'analytics_tracking_id' => '',
            ],
            'system' => [
                'maintenance_mode' => false,
                'two_factor_required' => false,
                'allow_self_service' => true,
                'auto_logout_minutes' => 60,
                'data_retention_days' => 365,
            ],
            'website' => [
                'hero_title' => 'Deliver with confidence',
                'hero_subtitle' => 'Baraka routes, tracks, and reconciles every parcel in real time.',
                'hero_cta_label' => 'Book a pickup',
                'footer_note' => 'Baraka ERP v1.0 • Crafted in Kampala',
            ],
            'shipping' => [
                'global_freight' => true,
                'default_sla_hours' => 48,
                'preferred_carrier' => 'Baraka Freight',
                'auto_rate_shop' => true,
                'customs_documents' => true,
                'returns_desk' => false,
            ],
            'branch_management' => [
                'regions_active' => ['Central', 'Eastern', 'Western', 'Northern'],
                'auto_assign_regions' => true,
                'require_branch_manager' => true,
                'review_cadence' => 'weekly',
                'max_branch_limit' => 12,
            ],
            'landing' => [
                'status' => 'live',
                'hero_headline' => 'Intelligent logistics OS for Africa',
                'hero_cta' => 'Customize landing',
                'announcement' => 'New automation framework deployed',
                'use_dark_theme' => true,
            ],
        ];
    }

    private function mergePreferences(array $existing = [], array $overrides = []): array
    {
        $defaults = $this->defaultPreferences();

        $existing = is_array($existing) ? $existing : [];
        $overrides = is_array($overrides) ? $overrides : [];

        $merged = array_replace_recursive($defaults, $existing, $overrides);

        foreach ($merged as $key => $value) {
            if (is_array($value)) {
                $merged[$key] = $this->normaliseBooleanValues($value);
            }
        }

        return $merged;
    }

    private function normaliseBooleanValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normaliseBooleanValues($value);
                continue;
            }

            if (in_array($value, ['true', 'false', '1', '0'], true)) {
                $data[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }

    public function preferences(GeneralSettings $settings): array
    {
        if ($this->supportsDetailsColumn) {
            return $this->mergePreferences($settings->details ?? []);
        }

        return $this->mergePreferences($this->readPreferencesFromDisk());
    }

    private function readPreferencesFromDisk(): array
    {
        try {
            if (! Storage::disk('local')->exists($this->preferencesPath())) {
                return [];
            }

            $payload = Storage::disk('local')->get($this->preferencesPath());
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
    }

    private function writePreferencesToDisk(array $preferences): void
    {
        try {
            Storage::disk('local')->makeDirectory('system');
            Storage::disk('local')->put($this->preferencesPath(), json_encode($preferences, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function preferencesPath(): string
    {
        return 'system/preferences.json';
    }
}
