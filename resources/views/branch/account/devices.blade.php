@extends('branch.layout')

@section('title', 'Account – Devices & Sessions')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Devices & Sessions</h1>
            <p class="muted text-sm">Manage your active sessions and view recent login activity.</p>
        </div>

        {{-- Active Sessions --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Active Sessions</div>
                    <div class="muted text-xs">Where you're currently signed in.</div>
                </div>
            </div>

            <div class="space-y-3">
                @php
                    // Mock sessions data - in production,this would come from session table
                    $currentSession = [
                        'id' => session()->getId(),
                        'device' => 'Desktop',
                        'browser' => 'Chrome 120',
                        'os' => 'Windows 11',
                        'ip_address' => request()->ip(),
                        'location' => 'Nairobi, Kenya',
                        'last_active' => now(),
                        'is_current' => true
                    ];

                    $otherSessions = [
                        [
                            'id' => 'sess_mobile_001',
                            'device' => 'Mobile',
                            'browser' => 'Safari 17',
                            'os' => 'iOS 17',
                            'ip_address' => '192.168.1.105',
                            'location' => 'Nairobi, Kenya',
                            'last_active' => now()->subHours(3),
                            'is_current' => false
                        ],
                        [
                            'id' => 'sess_desktop_002',
                            'device' => 'Desktop',
                            'browser' => 'Firefox 121',
                            'os' => 'macOS 14',
                            'ip_address' => '10.0.1.50',
                            'location' => 'Mombasa, Kenya',
                            'last_active' => now()->subDay(),
                            'is_current' => false
                        ],
                    ];

                    $allSessions = array_merge([$currentSession], $otherSessions);
                @endphp

                @foreach($allSessions as $session)
                    <div class="flex items-start gap-4 p-4 border border-white/10 rounded-lg {{ $session['is_current'] ? 'bg-emerald-500/5 border-emerald-500/30' : 'bg-white/5' }}">
                        <div class="mt-1">
                            @if($session['device'] === 'Mobile')
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="text-sm font-semibold">{{ $session['browser'] }} on {{ $session['os'] }}</div>
                                @if($session['is_current'])
                                    <span class="badge text-2xs bg-emerald-500/20 text-emerald-100 border border-emerald-500/30">Current</span>
                                @endif
                            </div>
                            <div class="space-y-0.5 text-xs muted">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ $session['location'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                    <span>{{ $session['ip_address'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ $session['last_active']->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        @if(!$session['is_current'])
                            <form method="POST" action="{{ route('branch.account.security.session.revoke', $session['id']) }}">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-rose-500/20 hover:bg-rose-500/30 text-rose-100 border border-rose-500/30 transition-colors"
                                        onclick="return confirm('Revoke this session? You will be logged out from that device.')">
                                    Revoke
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Login History --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Recent Login Activity</div>
                    <div class="muted text-xs">Last 30 days of login attempts.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th class="text-left">Date & Time</th>
                            <th class="text-left">Device</th>
                            <th class="text-left">Location</th>
                            <th class="text-left">IP Address</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $loginHistory = [
                                ['date' => now()->subHours(2), 'device' => 'Chrome 120 on Windows', 'location' => 'Nairobi, Kenya', 'ip' => request()->ip(), 'success' => true],
                                ['date' => now()->subHours(5), 'device' => 'Safari 17 on iOS', 'location' => 'Nairobi, Kenya', 'ip' => '192.168.1.105', 'success' => true],
                                ['date' => now()->subDay(), 'device' => 'Firefox 121 on macOS', 'location' => 'Mombasa, Kenya', 'ip' => '10.0.1.50', 'success' => true],
                                ['date' => now()->subDays(2), 'device' => 'Chrome 119 on Android', 'location' => 'Kisumu, Kenya', 'ip' => '41.90.64.12', 'success' => true],
                                ['date' => now()->subDays(3), 'device' => 'Chrome 120 on Windows', 'location' => 'Unknown', 'ip' => '103.255.4.88', 'success' => false],
                            ];
                        @endphp

                        @foreach($loginHistory as $login)
                            <tr>
                                <td class="text-sm">{{ $login['date']->format('M d, Y H:i') }}</td>
                                <td class="text-sm">{{ $login['device'] }}</td>
                                <td class="text-sm">{{ $login['location'] }}</td>
                                <td class="text-sm font-mono text-xs">{{ $login['ip'] }}</td>
                                <td>
                                    @if($login['success'])
                                        <span class="badge badge-success text-xs">Success</span>
                                    @else
                                        <span class="badge badge-danger text-xs">Failed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Security Tips --}}
        <div class="glass-panel p-6 space-y-4 border border-yellow-500/30 bg-yellow-500/5">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <div class="text-sm font-semibold text-yellow-100 mb-2">Security Tips</div>
                    <ul class="text-xs text-yellow-200/80 space-y-1.5 list-disc list-inside">
                        <li>Always log out from shared or public devices</li>
                        <li>Review your active sessions regularly and revoke any you don't recognize</li>
                        <li>Enable two-factor authentication for enhanced security</li>
                        <li>Be cautious of login attempts from unfamiliar locations</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
