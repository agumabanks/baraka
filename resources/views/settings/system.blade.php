@php
    // $settings is passed from controller as the 'system' section of preferences
    $s = $settings ?? [];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'System & Security')
@section('header', 'System & Security')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center shadow-lg">
                <i class="bi bi-shield-lock-fill text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">System & Security</h1>
                <p class="text-slate-500 dark:text-slate-400">Security, performance, and system administration</p>
            </div>
        </div>
        <button type="submit" form="systemForm" class="btn-primary">
            <i class="bi bi-check-lg mr-2"></i>
            Save Changes
        </button>
    </div>

    <!-- System Health Dashboard -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @php
            $health = [
                ['label' => 'Status', 'value' => app()->isDownForMaintenance() ? 'Maintenance' : 'Online', 'icon' => 'circle-fill', 'color' => app()->isDownForMaintenance() ? 'amber' : 'green'],
                ['label' => 'Environment', 'value' => ucfirst(config('app.env')), 'icon' => 'hdd-rack', 'color' => config('app.env') === 'production' ? 'green' : 'amber'],
                ['label' => 'PHP Version', 'value' => PHP_VERSION, 'icon' => 'filetype-php', 'color' => 'blue'],
                ['label' => 'Laravel', 'value' => app()->version(), 'icon' => 'box-seam', 'color' => 'red'],
            ];
        @endphp
        @foreach($health as $item)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="bi bi-{{ $item['icon'] }} text-{{ $item['color'] }}-500"></i>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $item['label'] }}</span>
                </div>
                <div class="text-lg font-bold text-slate-900 dark:text-white">{{ $item['value'] }}</div>
            </div>
        @endforeach
    </div>

    <form id="systemForm" method="POST" action="{{ route('settings.system.update') }}" class="ajax-form space-y-6">
        @csrf

        <!-- Authentication & Access -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-key text-amber-500"></i>
                <div>
                    <h3 class="pref-card-title">Authentication & Access</h3>
                    <p class="pref-card-desc">Login security and access controls</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Allow User Registration</span>
                        <span class="pref-hint">Let new users create accounts</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_registration" value="1" {{ !empty($s['allow_registration']) ? 'checked' : '' }}>
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
                            <input type="checkbox" name="require_email_verification" value="1" {{ ($s['require_email_verification'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Two-Factor Authentication</span>
                        <span class="pref-hint">2FA enforcement policy</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="2fa_enforcement" class="input-field w-full">
                            <option value="disabled" {{ ($s['2fa_enforcement'] ?? 'admin_required') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                            <option value="optional" {{ ($s['2fa_enforcement'] ?? 'admin_required') == 'optional' ? 'selected' : '' }}>Optional</option>
                            <option value="admin_required" {{ ($s['2fa_enforcement'] ?? 'admin_required') == 'admin_required' ? 'selected' : '' }}>Required for Admins</option>
                            <option value="all_required" {{ ($s['2fa_enforcement'] ?? 'admin_required') == 'all_required' ? 'selected' : '' }}>Required for All</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Session Timeout (minutes)</span>
                        <span class="pref-hint">Auto logout after inactivity</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="session_timeout" value="{{ $s['session_timeout'] ?? 120 }}" class="input-field w-full text-center" min="5" max="1440">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Max Concurrent Sessions</span>
                        <span class="pref-hint">Allow multiple login sessions per user</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="max_sessions" value="{{ $s['max_sessions'] ?? 3 }}" class="input-field w-full text-center" min="1" max="10">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Require Strong Passwords</span>
                        <span class="pref-hint">Enforce complex password requirements</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="strong_passwords" value="1" {{ ($s['strong_passwords'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Minimum Password Length</span>
                        <span class="pref-hint">Minimum characters required</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="password_min_length" value="{{ $s['password_min_length'] ?? 8 }}" class="input-field w-full text-center" min="6" max="32">
                    </div>
                </div>
            </div>
        </div>

        <!-- Brute Force Protection -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-shield-exclamation text-red-500"></i>
                <div>
                    <h3 class="pref-card-title">Brute Force Protection</h3>
                    <p class="pref-card-desc">Account lockout and rate limiting</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Max Login Attempts</span>
                        <span class="pref-hint">Failed attempts before lockout</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="max_login_attempts" value="{{ $s['max_login_attempts'] ?? 5 }}" class="input-field w-full text-center" min="3" max="20">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Lockout Duration (minutes)</span>
                        <span class="pref-hint">Account lockout time after max attempts</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="lockout_duration" value="{{ $s['lockout_duration'] ?? 15 }}" class="input-field w-full text-center" min="1" max="1440">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable IP Blocking</span>
                        <span class="pref-hint">Block suspicious IPs automatically</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="ip_blocking" value="1" {{ ($s['ip_blocking'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Security Alert Emails</span>
                        <span class="pref-hint">Email admins on security events</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="security_alerts" value="1" {{ ($s['security_alerts'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Security -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-braces text-blue-500"></i>
                <div>
                    <h3 class="pref-card-title">API Security</h3>
                    <p class="pref-card-desc">API rate limiting and authentication</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable API Access</span>
                        <span class="pref-hint">Allow external API integrations</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="api_enabled" value="1" {{ ($s['api_enabled'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>API Rate Limit (per minute)</span>
                        <span class="pref-hint">Maximum API requests per minute</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="api_rate_limit" value="{{ $s['api_rate_limit'] ?? 60 }}" class="input-field w-full text-center" min="10" max="1000">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>API Key Expiry (days)</span>
                        <span class="pref-hint">Days until API keys expire (0 = never)</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="api_key_expiry" value="{{ $s['api_key_expiry'] ?? 365 }}" class="input-field w-full text-center" min="0" max="730">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable API Logging</span>
                        <span class="pref-hint">Log all API requests for audit</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="api_logging" value="1" {{ ($s['api_logging'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data & Privacy -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-database-lock text-purple-500"></i>
                <div>
                    <h3 class="pref-card-title">Data & Privacy</h3>
                    <p class="pref-card-desc">Data retention and privacy controls</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Log Retention (days)</span>
                        <span class="pref-hint">Days to keep activity logs</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="log_retention_days" value="{{ $s['log_retention_days'] ?? 365 }}" class="input-field w-full text-center" min="30" max="2555">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Shipment Retention (years)</span>
                        <span class="pref-hint">Years to retain shipment records</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="shipment_retention_years" value="{{ $s['shipment_retention_years'] ?? 7 }}" class="input-field w-full text-center" min="1" max="10">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Data Encryption</span>
                        <span class="pref-hint">Encrypt sensitive data at rest</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="data_encryption" value="1" {{ ($s['data_encryption'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Mask PII in Logs</span>
                        <span class="pref-hint">Hide personal information in logs</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="mask_pii" value="1" {{ ($s['mask_pii'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-speedometer2 text-green-500"></i>
                <div>
                    <h3 class="pref-card-title">Performance</h3>
                    <p class="pref-card-desc">Caching and performance tuning</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Response Caching</span>
                        <span class="pref-hint">Cache API responses for performance</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="response_caching" value="1" {{ ($s['response_caching'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Cache TTL (seconds)</span>
                        <span class="pref-hint">How long to cache responses</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="cache_ttl" value="{{ $s['cache_ttl'] ?? 3600 }}" class="input-field w-full text-center" min="60" max="86400">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Query Logging</span>
                        <span class="pref-hint">Log slow database queries</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="query_logging" value="1" {{ !empty($s['query_logging']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug -->
        <div class="pref-card border-amber-200 dark:border-amber-900">
            <div class="pref-card-header">
                <i class="bi bi-bug text-amber-500"></i>
                <div>
                    <h3 class="pref-card-title">Debug & Development</h3>
                    <p class="pref-card-desc text-amber-600 dark:text-amber-400">Warning: Debug settings should be disabled in production</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Debug Mode</span>
                        <span class="pref-hint text-amber-600">Show detailed error messages</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="debug_mode" value="1" {{ !empty($s['debug_mode']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Log Level</span>
                        <span class="pref-hint">Minimum log severity to record</span>
                    </div>
                    <div class="pref-control w-40">
                        <select name="log_level" class="input-field w-full">
                            <option value="debug" {{ ($s['log_level'] ?? 'warning') == 'debug' ? 'selected' : '' }}>Debug</option>
                            <option value="info" {{ ($s['log_level'] ?? 'warning') == 'info' ? 'selected' : '' }}>Info</option>
                            <option value="warning" {{ ($s['log_level'] ?? 'warning') == 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="error" {{ ($s['log_level'] ?? 'warning') == 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-lightning text-yellow-500"></i>
                <div>
                    <h3 class="pref-card-title">Quick Actions</h3>
                    <p class="pref-card-desc">System maintenance operations</p>
                </div>
            </div>
            <div class="pref-card-body">
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="clearCache()" class="btn-secondary">
                        <i class="bi bi-trash mr-2"></i>Clear Cache
                    </button>
                    <button type="button" onclick="optimizeApp()" class="btn-secondary">
                        <i class="bi bi-lightning-charge mr-2"></i>Optimize
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function clearCache() {
    if (!confirm('Clear all application caches?')) return;
    fetch('{{ route("settings.clear-cache") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'Cache cleared!');
    })
    .catch(() => alert('Failed to clear cache'));
}

function optimizeApp() {
    alert('Optimization running... This may take a moment.');
}
</script>
@endsection
