@php
    // $settings is passed from controller with general settings data
    $s = $settings ?? [];
    $prefs = $preferenceMatrix ?? [];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'General Settings')
@section('header', 'General')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center shadow-lg">
                <i class="bi bi-gear-fill text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">General</h1>
                <p class="text-slate-500 dark:text-slate-400">Basic settings for your application</p>
            </div>
        </div>
        <button type="submit" form="generalSettingsForm" class="btn-primary shadow-lg shadow-blue-500/25">
            <i class="bi bi-check-lg mr-2"></i>
            Save Changes
        </button>
    </div>

    <form id="generalSettingsForm" method="POST" action="{{ route('settings.general.update') }}" class="ajax-form space-y-6">
        @csrf

        <!-- About This App -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-info-circle text-blue-500"></i>
                <div>
                    <h3 class="pref-card-title">About This App</h3>
                    <p class="pref-card-desc">Your application's identity and basic information</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Application Name</span>
                        <span class="pref-hint">Displayed in browser tabs and emails</span>
                    </div>
                    <div class="pref-control w-64">
                        <input type="text" name="app_name" value="{{ $s['app_name'] ?? config('app.name') }}" 
                               class="input-field w-full" placeholder="My Application">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Application URL</span>
                        <span class="pref-hint">The root URL of your application</span>
                    </div>
                    <div class="pref-control w-80">
                        <input type="url" name="app_url" value="{{ $s['app_url'] ?? config('app.url') }}" 
                               class="input-field w-full" placeholder="https://example.com">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Support Email</span>
                        <span class="pref-hint">Email address for customer support</span>
                    </div>
                    <div class="pref-control w-64">
                        <input type="email" name="support_email" value="{{ data_get($prefs, 'general.support_email', '') }}" 
                               class="input-field w-full" placeholder="support@example.com">
                    </div>
                </div>
            </div>
        </div>

        <!-- Language & Region -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-globe text-green-500"></i>
                <div>
                    <h3 class="pref-card-title">Language & Region</h3>
                    <p class="pref-card-desc">Localization and regional preferences</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Default Language</span>
                        <span class="pref-hint">Primary language for the application</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="app_locale" class="input-field w-full">
                            @foreach($locales ?? ['en' => 'English'] as $code => $label)
                                <option value="{{ $code }}" {{ ($s['app_locale'] ?? 'en') == $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Timezone</span>
                        <span class="pref-hint">Default timezone for dates and times</span>
                    </div>
                    <div class="pref-control w-64">
                        <select name="app_timezone" class="input-field w-full">
                            @foreach(['Africa/Kampala' => 'Africa/Kampala (EAT)', 'Africa/Nairobi' => 'Africa/Nairobi (EAT)', 'UTC' => 'UTC', 'Europe/London' => 'Europe/London (GMT)'] as $tz => $label)
                                <option value="{{ $tz }}" {{ ($s['app_timezone'] ?? 'Africa/Kampala') == $tz ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Default Currency</span>
                        <span class="pref-hint">Primary currency for transactions</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="default_currency" class="input-field w-full">
                            @foreach(['UGX' => 'UGX - Uganda Shilling', 'USD' => 'USD - US Dollar', 'EUR' => 'EUR - Euro', 'KES' => 'KES - Kenya Shilling'] as $code => $label)
                                <option value="{{ $code }}" {{ ($defaultCurrency ?? 'UGX') == $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Date Format</span>
                        <span class="pref-hint">How dates are displayed</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="date_format" class="input-field w-full">
                            @foreach(['d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'YYYY-MM-DD'] as $fmt => $label)
                                <option value="{{ $fmt }}" {{ (data_get($prefs, 'localization.date_format', 'd/m/Y')) == $fmt ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Behavior -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-sliders text-purple-500"></i>
                <div>
                    <h3 class="pref-card-title">System Behavior</h3>
                    <p class="pref-card-desc">Core application behavior settings</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Maintenance Mode</span>
                        <span class="pref-hint">Take the application offline for maintenance</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="maintenance_mode" value="1" {{ !empty($s['maintenance_mode']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Allow Registration</span>
                        <span class="pref-hint">Let new users create accounts</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_registration" value="1" {{ (data_get($prefs, 'system.allow_registration', false)) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Require Email Verification</span>
                        <span class="pref-hint">Verify email before account activation</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="require_email_verification" value="1" {{ (data_get($prefs, 'system.require_email_verification', true)) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Session Timeout (minutes)</span>
                        <span class="pref-hint">Auto logout after inactivity</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="session_timeout" value="{{ data_get($prefs, 'system.session_timeout', 120) }}" class="input-field w-full text-center" min="5" max="1440">
                    </div>
                </div>
            </div>
        </div>

        <!-- Environment Info (Read-Only) -->
        <div class="pref-card bg-slate-50 dark:bg-slate-800/50">
            <div class="pref-card-header">
                <i class="bi bi-terminal text-slate-500"></i>
                <div>
                    <h3 class="pref-card-title">Environment Info</h3>
                    <p class="pref-card-desc">Current system environment (read-only)</p>
                </div>
            </div>
            <div class="pref-card-body space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Environment</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ ucfirst($s['app_environment'] ?? config('app.env')) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Debug Mode</span>
                        <span class="text-sm font-medium {{ ($s['app_debug'] ?? config('app.debug')) ? 'text-amber-600' : 'text-green-600' }}">
                            {{ ($s['app_debug'] ?? config('app.debug')) ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">PHP Version</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ PHP_VERSION }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Laravel Version</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ app()->version() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
