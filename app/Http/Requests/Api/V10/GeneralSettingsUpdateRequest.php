<?php

namespace App\Http\Requests\Api\V10;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $preferences = $this->input('preferences');

        if (is_string($preferences)) {
            $decoded = json_decode($preferences, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'preferences' => $decoded,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'par_track_prefix' => ['nullable', 'string', 'max:10'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'primary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'text_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],

            'logo' => ['nullable', 'image', 'max:2048'],
            'light_logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],

            'preferences' => ['nullable', 'array'],
            'preferences.general' => ['nullable', 'array'],
            'preferences.general.support_email' => ['nullable', 'email', 'max:255'],
            'preferences.general.tagline' => ['nullable', 'string', 'max:255'],
            'preferences.general.timezone' => ['nullable', 'string', 'max:100'],
            'preferences.general.country' => ['nullable', 'string', 'max:100'],

            'preferences.branding' => ['nullable', 'array'],
            'preferences.branding.theme' => ['nullable', 'in:light,dark,system'],
            'preferences.branding.sidebar_density' => ['nullable', 'in:compact,comfortable,spacious'],
            'preferences.branding.enable_animations' => ['nullable', 'boolean'],

            'preferences.operations' => ['nullable', 'array'],
            'preferences.operations.auto_assign_drivers' => ['nullable', 'boolean'],
            'preferences.operations.enable_capacity_management' => ['nullable', 'boolean'],
            'preferences.operations.require_dispatch_approval' => ['nullable', 'boolean'],
            'preferences.operations.auto_generate_tracking_ids' => ['nullable', 'boolean'],
            'preferences.operations.enforce_pod_otp' => ['nullable', 'boolean'],
            'preferences.operations.allow_public_tracking' => ['nullable', 'boolean'],

            'preferences.finance' => ['nullable', 'array'],
            'preferences.finance.auto_reconcile' => ['nullable', 'boolean'],
            'preferences.finance.enforce_cod_settlement_workflow' => ['nullable', 'boolean'],
            'preferences.finance.enable_invoice_emails' => ['nullable', 'boolean'],
            'preferences.finance.default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'preferences.finance.rounding_mode' => ['nullable', 'in:none,nearest,up,down'],

            'preferences.notifications' => ['nullable', 'array'],
            'preferences.notifications.email' => ['nullable', 'boolean'],
            'preferences.notifications.sms' => ['nullable', 'boolean'],
            'preferences.notifications.push' => ['nullable', 'boolean'],
            'preferences.notifications.daily_digest' => ['nullable', 'boolean'],
            'preferences.notifications.escalate_incidents' => ['nullable', 'boolean'],

            'preferences.integrations' => ['nullable', 'array'],
            'preferences.integrations.webhooks_enabled' => ['nullable', 'boolean'],
            'preferences.integrations.webhooks_url' => ['nullable', 'url'],
            'preferences.integrations.slack_enabled' => ['nullable', 'boolean'],
            'preferences.integrations.slack_channel' => ['nullable', 'string', 'max:255'],
            'preferences.integrations.power_bi_enabled' => ['nullable', 'boolean'],
            'preferences.integrations.zapier_enabled' => ['nullable', 'boolean'],
            'preferences.integrations.analytics_tracking_id' => ['nullable', 'string', 'max:255'],

            'preferences.system' => ['nullable', 'array'],
            'preferences.system.maintenance_mode' => ['nullable', 'boolean'],
            'preferences.system.two_factor_required' => ['nullable', 'boolean'],
            'preferences.system.allow_self_service' => ['nullable', 'boolean'],
            'preferences.system.auto_logout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'preferences.system.data_retention_days' => ['nullable', 'integer', 'min:30', 'max:1825'],
        ];
    }
}
