@php
    $security = $settings['security'] ?? [];
@endphp

<form id="securityForm" onsubmit="event.preventDefault(); saveSettings('securityForm', '{{ route('branch.settings.security') }}');">
    @csrf
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Authentication -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Authentication</h3>
                <p class="text-xs text-slate-400 mt-1">Security requirements for branch users</p>
            </div>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Require Two-Factor Authentication</span>
                        <p class="text-2xs text-slate-500">All users must enable 2FA to access this branch</p>
                    </div>
                    <input type="checkbox" name="require_2fa" value="1" {{ ($security['require_2fa'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Session Timeout (minutes)</label>
                    <select name="session_timeout" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">Inherit from system</option>
                        <option value="15" @selected(($security['session_timeout'] ?? '') == 15)>15 minutes</option>
                        <option value="30" @selected(($security['session_timeout'] ?? '') == 30)>30 minutes</option>
                        <option value="60" @selected(($security['session_timeout'] ?? '') == 60)>1 hour</option>
                        <option value="120" @selected(($security['session_timeout'] ?? '') == 120)>2 hours</option>
                        <option value="240" @selected(($security['session_timeout'] ?? '') == 240)>4 hours</option>
                        <option value="480" @selected(($security['session_timeout'] ?? '') == 480)>8 hours</option>
                    </select>
                    <p class="text-2xs text-slate-500 mt-1">Auto-logout after inactivity period</p>
                </div>
            </div>
        </div>

        <!-- IP Restrictions -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">IP Restrictions</h3>
                <p class="text-xs text-slate-400 mt-1">Limit access to specific IP addresses</p>
            </div>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Enable IP Whitelist</span>
                        <p class="text-2xs text-slate-500">Only allow access from specified IPs</p>
                    </div>
                    <input type="checkbox" name="ip_whitelist_enabled" value="1" {{ ($security['ip_whitelist_enabled'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Allowed IP Addresses</label>
                    <textarea name="ip_whitelist" rows="4" placeholder="Enter one IP address per line&#10;e.g., 192.168.1.100&#10;10.0.0.0/24"
                              class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 font-mono resize-none">{{ is_array($security['ip_whitelist'] ?? null) ? implode("\n", $security['ip_whitelist']) : ($security['ip_whitelist'] ?? '') }}</textarea>
                    <p class="text-2xs text-slate-500 mt-1">Supports individual IPs and CIDR notation</p>
                </div>
            </div>
        </div>

        <!-- Audit & Compliance -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Audit & Compliance</h3>
                <p class="text-xs text-slate-400 mt-1">Logging and data retention settings</p>
            </div>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Enhanced Audit Logging</span>
                        <p class="text-2xs text-slate-500">Log all user actions for compliance</p>
                    </div>
                    <input type="checkbox" name="audit_logging" value="1" {{ ($security['audit_logging'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Data Retention Period</label>
                    <select name="data_retention_days" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">Inherit from system</option>
                        <option value="90" @selected(($security['data_retention_days'] ?? '') == 90)>90 days</option>
                        <option value="180" @selected(($security['data_retention_days'] ?? '') == 180)>180 days</option>
                        <option value="365" @selected(($security['data_retention_days'] ?? '') == 365)>1 year</option>
                        <option value="730" @selected(($security['data_retention_days'] ?? '') == 730)>2 years</option>
                        <option value="1825" @selected(($security['data_retention_days'] ?? '') == 1825)>5 years</option>
                        <option value="2555" @selected(($security['data_retention_days'] ?? '') == 2555)>7 years</option>
                    </select>
                    <p class="text-2xs text-slate-500 mt-1">How long to retain shipment and audit data</p>
                </div>
            </div>
        </div>

        <!-- Security Status -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Security Status</h3>
                <p class="text-xs text-slate-400 mt-1">Current security posture for this branch</p>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full {{ ($security['require_2fa'] ?? false) ? 'bg-emerald-500/20' : 'bg-amber-500/20' }} flex items-center justify-center">
                            <i class="bi bi-shield-{{ ($security['require_2fa'] ?? false) ? 'check' : 'exclamation' }} {{ ($security['require_2fa'] ?? false) ? 'text-emerald-400' : 'text-amber-400' }}"></i>
                        </div>
                        <span class="text-sm text-white">Two-Factor Authentication</span>
                    </div>
                    <span class="px-2 py-1 text-2xs font-medium rounded {{ ($security['require_2fa'] ?? false) ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                        {{ ($security['require_2fa'] ?? false) ? 'Required' : 'Optional' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full {{ ($security['ip_whitelist_enabled'] ?? false) ? 'bg-emerald-500/20' : 'bg-slate-500/20' }} flex items-center justify-center">
                            <i class="bi bi-globe {{ ($security['ip_whitelist_enabled'] ?? false) ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        </div>
                        <span class="text-sm text-white">IP Restrictions</span>
                    </div>
                    <span class="px-2 py-1 text-2xs font-medium rounded {{ ($security['ip_whitelist_enabled'] ?? false) ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-500/20 text-slate-400' }}">
                        {{ ($security['ip_whitelist_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full {{ ($security['audit_logging'] ?? true) ? 'bg-emerald-500/20' : 'bg-rose-500/20' }} flex items-center justify-center">
                            <i class="bi bi-journal-text {{ ($security['audit_logging'] ?? true) ? 'text-emerald-400' : 'text-rose-400' }}"></i>
                        </div>
                        <span class="text-sm text-white">Audit Logging</span>
                    </div>
                    <span class="px-2 py-1 text-2xs font-medium rounded {{ ($security['audit_logging'] ?? true) ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                        {{ ($security['audit_logging'] ?? true) ? 'Active' : 'Disabled' }}
                    </span>
                </div>
            </div>

            <!-- Security Score -->
            <div class="bg-obsidian-700/50 rounded-lg p-4 border border-white/5">
                @php
                    $score = 0;
                    if ($security['require_2fa'] ?? false) $score += 40;
                    if ($security['ip_whitelist_enabled'] ?? false) $score += 30;
                    if ($security['audit_logging'] ?? true) $score += 30;
                    $scoreColor = $score >= 80 ? 'emerald' : ($score >= 50 ? 'amber' : 'rose');
                @endphp
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-300">Security Score</span>
                    <span class="text-2xl font-bold text-{{ $scoreColor }}-400">{{ $score }}%</span>
                </div>
                <div class="w-full bg-obsidian-600 rounded-full h-2">
                    <div class="bg-{{ $scoreColor }}-500 h-2 rounded-full transition-all" style="width: {{ $score }}%"></div>
                </div>
                <p class="text-2xs text-slate-500 mt-2">
                    @if($score >= 80)
                        Excellent! Your branch has strong security controls.
                    @elseif($score >= 50)
                        Good, but consider enabling additional security features.
                    @else
                        Warning: Consider enabling more security controls.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-check-lg mr-2"></i>Save Security Settings
        </button>
    </div>
</form>
