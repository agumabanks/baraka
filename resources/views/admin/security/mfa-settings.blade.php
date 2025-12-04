@extends('admin.layout')

@section('title', 'MFA Policy Settings')
@section('header', 'MFA Policy Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Adoption Statistics --}}
    <div class="grid gap-4 md:grid-cols-4">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Users</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_users']) }}</div>
        </div>
        <div class="stat-card border-emerald-500/30">
            <div class="muted text-xs uppercase">MFA Enabled</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['users_with_mfa']) }}</div>
        </div>
        <div class="stat-card border-sky-500/30">
            <div class="muted text-xs uppercase">Adoption Rate</div>
            <div class="text-2xl font-bold text-sky-400">{{ $stats['adoption_rate'] }}%</div>
        </div>
        <div class="stat-card border-purple-500/30">
            <div class="muted text-xs uppercase">TOTP Users</div>
            <div class="text-2xl font-bold text-purple-400">{{ $stats['totp_count'] }}</div>
        </div>
    </div>

    {{-- Policy Settings Form --}}
    <div class="glass-panel p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold">MFA Enforcement Policy</h3>
                <p class="text-sm muted">Configure how two-factor authentication is enforced across the organization.</p>
            </div>
            <a href="{{ route('admin.security.mfa') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to MFA
            </a>
        </div>

        <form id="mfa-policy-form" class="space-y-6">
            @csrf
            
            {{-- Enforcement Level --}}
            <div>
                <label class="block text-sm font-medium mb-3">MFA Enforcement Level</label>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="flex items-start gap-3 p-4 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="radio" name="mfa_enforcement" value="disabled" class="mt-1" {{ ($settings['mfa_enforcement'] ?? 'optional') === 'disabled' ? 'checked' : '' }}>
                        <div>
                            <div class="font-medium">Disabled</div>
                            <div class="text-sm muted">MFA is turned off for all users.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="radio" name="mfa_enforcement" value="optional" class="mt-1" {{ ($settings['mfa_enforcement'] ?? 'optional') === 'optional' ? 'checked' : '' }}>
                        <div>
                            <div class="font-medium">Optional</div>
                            <div class="text-sm muted">Users can choose to enable MFA.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="radio" name="mfa_enforcement" value="required_for_admins" class="mt-1" {{ ($settings['mfa_enforcement'] ?? 'optional') === 'required_for_admins' ? 'checked' : '' }}>
                        <div>
                            <div class="font-medium">Required for Admins</div>
                            <div class="text-sm muted">Admins must enable MFA, optional for others.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="radio" name="mfa_enforcement" value="required" class="mt-1" {{ ($settings['mfa_enforcement'] ?? 'optional') === 'required' ? 'checked' : '' }}>
                        <div>
                            <div class="font-medium">Required for All</div>
                            <div class="text-sm muted">All users must enable MFA to access the system.</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Grace Period --}}
            <div>
                <label class="block text-sm font-medium mb-2">Grace Period (Days)</label>
                <p class="text-sm muted mb-3">Number of days users have to set up MFA after it becomes required.</p>
                <input type="number" name="mfa_grace_period_days" value="{{ $settings['mfa_grace_period_days'] ?? 7 }}" min="0" max="30" class="w-full md:w-48 bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
            </div>

            {{-- Required Roles --}}
            <div>
                <label class="block text-sm font-medium mb-2">Roles Requiring MFA</label>
                <p class="text-sm muted mb-3">Select which roles must have MFA enabled (when using "Required for Admins" mode).</p>
                <div class="flex flex-wrap gap-2">
                    @php
                        $requiredRoles = $settings['mfa_required_for_roles'] ?? ['admin', 'super-admin'];
                        $allRoles = ['admin', 'super-admin', 'branch_manager', 'operations_manager', 'finance_manager'];
                    @endphp
                    @foreach($allRoles as $role)
                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                            <input type="checkbox" name="mfa_required_for_roles[]" value="{{ $role }}" {{ in_array($role, $requiredRoles) ? 'checked' : '' }}>
                            <span class="text-sm">{{ ucwords(str_replace('_', ' ', $role)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Allowed Methods --}}
            <div>
                <label class="block text-sm font-medium mb-2">Allowed MFA Methods</label>
                <p class="text-sm muted mb-3">Select which MFA methods users can choose from.</p>
                <div class="flex flex-wrap gap-2">
                    @php
                        $allowedMethods = $settings['mfa_allowed_methods'] ?? ['totp', 'sms', 'email'];
                    @endphp
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="checkbox" name="mfa_allowed_methods[]" value="totp" {{ in_array('totp', $allowedMethods) ? 'checked' : '' }}>
                        <span class="text-sm">Authenticator App (TOTP)</span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="checkbox" name="mfa_allowed_methods[]" value="sms" {{ in_array('sms', $allowedMethods) ? 'checked' : '' }}>
                        <span class="text-sm">SMS</span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 cursor-pointer hover:bg-white/10 transition">
                        <input type="checkbox" name="mfa_allowed_methods[]" value="email" {{ in_array('email', $allowedMethods) ? 'checked' : '' }}>
                        <span class="text-sm">Email</span>
                    </label>
                </div>
            </div>

            {{-- Remember Device --}}
            <div>
                <label class="block text-sm font-medium mb-2">Remember Device Duration (Days)</label>
                <p class="text-sm muted mb-3">How long a trusted device can skip MFA verification.</p>
                <input type="number" name="mfa_remember_device_days" value="{{ $settings['mfa_remember_device_days'] ?? 30 }}" min="0" max="90" class="w-full md:w-48 bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-between pt-6 border-t border-white/10">
                <p class="text-sm muted">Changes will apply immediately to all users.</p>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    {{-- MFA Methods Usage --}}
    <div class="glass-panel p-6">
        <h3 class="text-lg font-semibold mb-4">MFA Methods Usage</h3>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="p-4 rounded-lg bg-white/5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <div class="font-semibold">Authenticator App</div>
                        <div class="text-sm muted">TOTP</div>
                    </div>
                </div>
                <div class="text-3xl font-bold text-purple-400">{{ $stats['totp_count'] }}</div>
                <div class="text-sm muted">active users</div>
            </div>
            <div class="p-4 rounded-lg bg-white/5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </div>
                    <div>
                        <div class="font-semibold">SMS</div>
                        <div class="text-sm muted">Text Message</div>
                    </div>
                </div>
                <div class="text-3xl font-bold text-sky-400">{{ $stats['sms_count'] }}</div>
                <div class="text-sm muted">active users</div>
            </div>
            <div class="p-4 rounded-lg bg-white/5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <div class="font-semibold">Email</div>
                        <div class="text-sm muted">Email Codes</div>
                    </div>
                </div>
                <div class="text-3xl font-bold text-amber-400">{{ $stats['email_count'] }}</div>
                <div class="text-sm muted">active users</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('mfa-policy-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Handle arrays
    data.mfa_enforcement = formData.get('mfa_enforcement');
    data.mfa_grace_period_days = parseInt(formData.get('mfa_grace_period_days'));
    data.mfa_remember_device_days = parseInt(formData.get('mfa_remember_device_days'));
    data.mfa_required_for_roles = formData.getAll('mfa_required_for_roles[]');
    data.mfa_allowed_methods = formData.getAll('mfa_allowed_methods[]');
    
    if (data.mfa_allowed_methods.length === 0) {
        alert('Please select at least one MFA method.');
        return;
    }
    
    try {
        const res = await fetch('{{ route("admin.security.mfa-settings.update") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert('MFA policy settings saved successfully.');
        } else {
            alert(result.message || 'Failed to save settings.');
        }
    } catch (e) {
        alert('Error saving settings: ' + e.message);
    }
});
</script>
@endpush
@endsection
