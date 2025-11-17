<?php

namespace App\Http\Requests;

use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Comprehensive FormRequest validation for Settings endpoints
 * 
 * Handles complex form payload validation including:
 * - Core database fields
 * - Nested preferences structure
 * - File uploads for logos and favicon
 * - Custom validation rules for business logic
 */
class SettingsFormRequest extends FormRequest
{
    protected $repo;
    
    public function __construct(GeneralSettingsInterface $repo)
    {
        $this->repo = $repo;
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage settings
        return auth()->check() && auth()->user()->can('manage-settings');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Handle JSON string preferences
        $preferences = $this->input('preferences');
        if (is_string($preferences)) {
            $decoded = json_decode($preferences, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'preferences' => $decoded,
                ]);
            }
        }

        // Convert string boolean values to actual booleans
        $this->convertStringBooleans();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [];

        // Core database fields validation
        $rules = array_merge($rules, $this->getCoreFieldRules());

        // File upload validation
        $rules = array_merge($rules, $this->getFileUploadRules());

        // Preferences validation
        $rules = array_merge($rules, $this->getPreferencesValidationRules());

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Core field messages
            'name.required' => __('settings.validation.name_required'),
            'name.max' => __('settings.validation.name_max'),
            'email.email' => __('settings.validation.email_format'),
            'phone.regex' => __('settings.validation.phone_format'),
            'currency.exists' => __('settings.validation.currency_invalid'),
            'primary_color.hex_color' => __('settings.validation.primary_color_format'),
            'text_color.hex_color' => __('settings.validation.text_color_format'),

            // File upload messages
            'logo.image' => __('settings.validation.logo_image_required'),
            'logo.mimes' => __('settings.validation.logo_mimes'),
            'logo.max' => __('settings.validation.logo_max_size'),
            'light_logo.image' => __('settings.validation.light_logo_image_required'),
            'light_logo.mimes' => __('settings.validation.light_logo_mimes'),
            'light_logo.max' => __('settings.validation.light_logo_max_size'),
            'favicon.image' => __('settings.validation.favicon_image_required'),
            'favicon.mimes' => __('settings.validation.favicon_mimes'),
            'favicon.max' => __('settings.validation.favicon_max_size'),

            // Preferences messages
            'preferences.general.support_email.email' => __('settings.validation.support_email_format'),
            'preferences.system.auto_logout_minutes.numeric' => __('settings.validation.auto_logout_numeric'),
            'preferences.system.data_retention_days.numeric' => __('settings.validation.retention_days_numeric'),
            'preferences.finance.default_tax_rate.numeric' => __('settings.validation.tax_rate_numeric'),
            'preferences.finance.default_tax_rate.min' => __('settings.validation.tax_rate_min'),
            'preferences.finance.default_tax_rate.max' => __('settings.validation.tax_rate_max'),
            'preferences.integrations.webhooks_url.url' => __('settings.validation.webhooks_url_format'),
        ];
    }

    /**
     * Core database field validation rules
     */
    protected function getCoreFieldRules(): array
    {
        $activeCurrencies = $this->getActiveCurrencyCodes();
        
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10', Rule::in($activeCurrencies)],
            'copyright' => ['nullable', 'string', 'max:255'],
            'par_track_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'invoice_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'primary_color' => ['nullable', 'string', new \App\Rules\HexColorRule],
            'text_color' => ['nullable', 'string', new \App\Rules\HexColorRule],
        ];
    }

    /**
     * File upload validation rules
     */
    protected function getFileUploadRules(): array
    {
        return [
            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,svg',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=50,max_width=500,max_height=200'
            ],
            'light_logo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,svg',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=50,max_width=500,max_height=200'
            ],
            'favicon' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,ico,svg',
                'max:1024', // 1MB
                'dimensions:min_width=16,min_height=16,max_width=64,max_height=64'
            ],
        ];
    }

    /**
     * Preferences nested validation structure
     */
    protected function getPreferencesValidationRules(): array
    {
        $rules = [
            'preferences' => ['nullable', 'array'],
        ];

        // Get default preferences structure for dynamic validation
        $defaultPreferences = $this->repo->all()->details ?? $this->repo->defaultPreferences();

        foreach ($defaultPreferences as $section => $sectionData) {
            $rules = array_merge($rules, $this->getSectionValidationRules($section, $sectionData));
        }

        return $rules;
    }

    /**
     * Generate validation rules for a specific preferences section
     */
    protected function getSectionValidationRules(string $section, array $sectionData): array
    {
        $rules = [];
        $sectionPrefix = "preferences.{$section}";

        $rules["{$sectionPrefix}"] = ['nullable', 'array'];

        foreach ($sectionData as $key => $value) {
            $fieldPath = "{$sectionPrefix}.{$key}";
            $rules[$fieldPath] = $this->getFieldValidationRules($value);
        }

        return $rules;
    }

    /**
     * Get validation rules for individual preference fields
     */
    protected function getFieldValidationRules($value): array
    {
        $rules = [];

        if (is_bool($value)) {
            $rules = ['nullable', 'boolean'];
        } elseif (is_numeric($value)) {
            $rules = ['nullable', 'numeric'];
        } elseif (is_string($value)) {
            $rules = ['nullable', 'string', 'max:255'];
        }

        // Add specific validation based on field patterns
        $fieldName = last(explode('.', $this->currentField ?? ''));
        
        switch ($fieldName) {
            case 'email':
                $rules = ['nullable', 'email', 'max:255'];
                break;
            case 'url':
                $rules = ['nullable', 'url'];
                break;
            case 'timezone':
                $rules = ['nullable', 'string', 'max:100'];
                break;
            case 'theme':
                $rules = ['nullable', 'string', 'in:light,dark,system'];
                break;
            case 'sidebar_density':
                $rules = ['nullable', 'string', 'in:compact,comfortable,spacious'];
                break;
            case 'rounding_mode':
                $rules = ['nullable', 'string', 'in:none,nearest,up,down'];
                break;
            case 'auto_logout_minutes':
                $rules = ['nullable', 'integer', 'min:5', 'max:1440'];
                break;
            case 'data_retention_days':
                $rules = ['nullable', 'integer', 'min:30', 'max:1825'];
                break;
            case 'default_tax_rate':
                $rules = ['nullable', 'numeric', 'min:0', 'max:100'];
                break;
        }

        return $rules;
    }

    /**
     * Convert string boolean values to actual booleans for preferences
     */
    protected function convertStringBooleans(): void
    {
        $preferences = $this->input('preferences', []);
        
        if (is_array($preferences)) {
            $converted = $this->normalizeBooleanValues($preferences);
            $this->merge(['preferences' => $converted]);
        }
    }

    /**
     * Normalize boolean values in array
     */
    protected function normalizeBooleanValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeBooleanValues($value);
                continue;
            }

            if (in_array($value, ['true', 'false', '1', '0'], true)) {
                $data[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }

    /**
     * Get active currency codes for validation
     */
    protected function getActiveCurrencyCodes(): array
    {
        try {
            $currencies = app(\App\Repositories\Currency\CurrencyInterface::class)->getActive();
            return $currencies->pluck('code')->toArray();
        } catch (\Exception $e) {
            // Return common currency codes as fallback
            return ['USD', 'EUR', 'GBP', 'UGX', 'KES', 'TZS', 'RWF'];
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->wantsJson()) {
            $this->throwValidationException($validator);
        }

        // For web requests, let the controller handle the error
        parent::failedValidation($validator);
    }
}