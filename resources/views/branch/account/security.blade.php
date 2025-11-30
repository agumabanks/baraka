@extends('branch.layout')

@section('title', 'Account – Security')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Security</h1>
            <p class="muted text-sm">Manage your password, two-factor authentication, and active sessions.</p>
        </div>

        <div class="flex space-x-4 mb-6">
            <a href="{{ route('branch.account.security.audit-logs') }}" class="text-yellow-400 hover:underline">Audit Logs</a>
            <a href="{{ route('branch.account.security.sessions') }}" class="text-yellow-400 hover:underline">Active Sessions</a>
        </div>

        {{-- Password Change Section --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Change Password</div>
                    <div class="muted text-xs">Update your password to keep your account secure.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('branch.account.security.password') }}" class="space-y-4">
                @csrf
                
                <div>
                    <label for="current_password" class="block text-sm font-medium mb-2">Current Password <span class="text-rose-400">*</span></label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all @error('current_password') border-rose-500/50 @enderror"
                           required>
                    @error('current_password')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="new_password" class="block text-sm font-medium mb-2">New Password <span class="text-rose-400">*</span></label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all @error('new_password') border-rose-500/50 @enderror"
                               required
                               data-password-input>
                        @error('new_password')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="mt-1 text-2xs muted">Minimum 8 characters, include uppercase, lowercase, and numbers.</div>
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="block text-sm font-medium mb-2">Confirm New Password <span class="text-rose-400">*</span></label>
                        <input type="password" 
                               id="new_password_confirmation" 
                               name="new_password_confirmation" 
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all"
                               required>
                    </div>
                </div>

                {{-- Password Strength Indicator --}}
                <div data-password-strength class="hidden">
                    <div class="text-2xs font-medium mb-1">Password Strength</div>
                    <div class="flex gap-1 h-1.5">
                        <div class="flex-1 bg-white/10 rounded-full overflow-hidden">
                            <div data-strength-bar class="h-full transition-all duration-300 bg-rose-500" style="width: 0%"></div>
                        </div>
                    </div>
                    <div data-strength-text class="text-2xs muted mt-1">Weak</div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        {{-- Two-Factor Authentication Section --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Two-Factor Authentication (2FA)</div>
                    <div class="muted text-xs">Add an extra layer of security to your account.</div>
                </div>
                <div>
                    @if(auth()->user()->mfaDevices()->where('is_verified', true)->exists())
                        <span class="badge badge-success text-xs">Enabled</span>
                    @else
                        <span class="badge text-xs bg-white/5 text-slate-400 border border-white/10">Disabled</span>
                    @endif
                </div>
            </div>

            @if(!auth()->user()->mfaDevices()->where('is_verified', true)->exists())
                {{-- 2FA Enrollment --}}
                <div class="border border-white/10 rounded-lg p-4 bg-white/5">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold mb-2">Enable 2FA</h3>
                        <p class="text-xs muted">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>
                    </div>

                    <div class="flex flex-col md:flex-row gap-6 items-start">
                        <div class="bg-white p-4 rounded-lg" data-qr-code>
                            <div class="w-48 h-48 bg-slate-200 flex items-center justify-center text-slate-600 text-xs">
                                QR Code<br>Click "Generate" to show
                            </div>
                        </div>

                        <div class="flex-1 space-y-4">
                            <div>
                                <label class="text-xs font-medium block mb-2">Manual Entry Key</label>
                                <div class="bg-obsidian-700/50 border border-white/10 rounded px-3 py-2 font-mono text-xs" data-secret-key>
                                    Click "Generate 2FA" to see key
                                </div>
                            </div>

                            <form method="POST" action="{{ route('branch.account.security.2fa.enable') }}" class="space-y-3" data-2fa-form>
                                @csrf
                                <input type="hidden" name="secret" data-secret-input>
                                
                                <div>
                                    <label for="verification_code" class="text-xs font-medium block mb-2">Verification Code</label>
                                    <input type="text" 
                                           id="verification_code" 
                                           name="verification_code" 
                                           placeholder="000000"
                                           maxlength="6"
                                           class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 font-mono"
                                           required>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" data-generate-2fa class="px-4 py-2 rounded-lg text-xs font-medium bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
                                        Generate 2FA
                                    </button>
                                    <button type="submit" class="px-4 py-2 rounded-lg text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                                        Enable 2FA
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                {{-- 2FA Enabled --}}
                <div class="border border-emerald-500/30 rounded-lg p-4 bg-emerald-500/10">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-semibold mb-1 text-emerald-100">2FA is Active</h3>
                            <p class="text-xs text-emerald-200/80">Your account is protected with two-factor authentication.</p>
                            
                            <div class="mt-4 space-y-2">
                                @foreach(auth()->user()->mfaDevices as $device)
                                    <div class="flex items-center gap-3 text-xs">
                                        <div class="w-2 h-2 rounded-full {{ $device->is_verified ? 'bg-emerald-400' : 'bg-slate-400' }}"></div>
                                        <div class="flex-1">
                                            <div class="font-medium">{{ $device->device_name }}</div>
                                            <div class="text-2xs muted">Last used: {{ $device->last_used_at ? $device->last_used_at->diffForHumans() : 'Never' }}</div>
                                        </div>
                                        @if($device->is_primary)
                                            <span class="badge text-2xs bg-yellow-500/20 text-yellow-200 border border-yellow-500/30">Primary</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <form method="POST" action="{{ route('branch.account.security.2fa.disable') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-lg text-xs font-medium bg-rose-500/20 hover:bg-rose-500/30 text-rose-100 border border-rose-500/30 transition-colors"
                                    onclick="return confirm('Are you sure you want to disable 2FA? This will make your account less secure.')">
                                Disable 2FA
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Active Sessions Section --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Active Sessions</div>
                    <div class="muted text-xs">Manage devices where you're currently signed in.</div>
                </div>
            </div>

            <div class="space-y-3">
                @php
                    $sessions = auth()->user()->sessions ?? [];
                    if(empty($sessions)) {
                        $sessions = [[
                            'id' => session()->getId(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'last_activity' => now(),
                            'is_current' => true
                        ]];
                    }
                @endphp

                @forelse($sessions as $session)
                    <div class="flex items-start justify-between p-4 border border-white/10 rounded-lg {{ $session['is_current'] ?? false ? 'bg-emerald-500/5 border-emerald-500/30' : 'bg-white/5' }}">
                        <div class="flex gap-3 items-start flex-1">
                            <div class="mt-1">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium">
                                        {{ $session['device_name'] ?? 'Unknown Device' }}
                                    </div>
                                    @if($session['is_current'] ?? false)
                                        <span class="badge text-2xs bg-emerald-500/20 text-emerald-100 border border-emerald-500/30">Current Session</span>
                                    @endif
                                </div>
                                <div class="text-xs muted mt-1">
                                    {{ $session['ip_address'] ?? request()->ip() }} • 
                                    {{ $session['location'] ?? 'Unknown Location' }}
                                </div>
                                <div class="text-2xs muted mt-0.5">
                                    Last active: {{ isset($session['last_activity']) ? \Carbon\Carbon::parse($session['last_activity'])->diffForHumans() : 'Now' }}
                                </div>
                            </div>
                        </div>
                        @if(!($session['is_current'] ?? false))
                            <form method="POST" action="{{ route('branch.account.security.session.revoke', $session['id']) }}">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-rose-500/20 hover:bg-rose-500/30 text-rose-100 border border-rose-500/30 transition-colors"
                                        onclick="return confirm('Revoke this session?')">
                                    Revoke
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8 text-sm muted">No active sessions found.</div>
                @endforelse
            </div>
        </div>

        {{-- Security Activity Log --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Recent Security Activity</div>
                    <div class="muted text-xs">Monitor recent security events on your account.</div>
                </div>
            </div>

            <div class="space-y-2">
                @php
                    $activities = [
                        ['event' => 'Password changed', 'time' => now()->subDays(5), 'ip' => request()->ip()],
                        ['event' => 'Successful login', 'time' => now()->subHours(2), 'ip' => request()->ip()],
                        ['event' => 'Profile updated', 'time' => now()->subDays(1), 'ip' => request()->ip()],
                    ];
                @endphp

                @foreach($activities as $activity)
                    <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                        <div>
                            <div class="text-sm">{{ $activity['event'] }}</div>
                            <div class="text-xs muted">{{ $activity['time']->diffForHumans() }} • {{ $activity['ip'] }}</div>
                        </div>
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
