@extends('branch.layout')

@section('title', 'Account – Notifications')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Notifications</h1>
            <p class="muted text-sm">Configure how and when you receive notifications.</p>
        </div>

        <form method="POST" action="{{ route('branch.account.notifications.update') }}">
            @csrf
            @method('PUT')

            {{-- Email Notifications --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">Email Notifications</div>
                    <div class="muted text-xs">Receive updates via email.</div>
                </div>

                <div class="space-y-4">
                    @php
                        $prefs = json_decode(auth()->user()->notification_prefs ?? '{}', true);
                        $emailPrefs = $prefs['email'] ?? [];
                    @endphp

                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Shipment Updates</div>
                            <div class="text-xs muted">Get notified about shipment status changes</div>
                        </div>
                        <input type="checkbox" name="email[shipments]" value="1" {{ isset($emailPrefs['shipments']) && $emailPrefs['shipments'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>

                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Operations Alerts</div>
                            <div class="text-xs muted">Urgent operational notifications</div>
                        </div>
                        <input type="checkbox" name="email[operations]" value="1" {{ isset($emailPrefs['operations']) && $emailPrefs['operations'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>

                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">System Alerts</div>
                            <div class="text-xs muted">Security and system notifications</div>
                        </div>
                        <input type="checkbox" name="email[system]" value="1" {{ isset($emailPrefs['system']) && $emailPrefs['system'] ? 'checked' : '' }} class="sr-only peer" checked disabled>
                        <div class="relative w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 opacity-50 cursor-not-allowed"></div>
                    </label>
                    <p class="text-2xs muted">System alerts cannot be disabled for security reasons.</p>

                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Weekly Reports</div>
                            <div class="text-xs muted">Summary emails every Monday</div>
                        </div>
                        <input type="checkbox" name="email[reports]" value="1" {{ isset($emailPrefs['reports']) && $emailPrefs['reports'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>
            </div>

            {{-- SMS Notifications --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">SMS Notifications</div>
                    <div class="muted text-xs">Receive critical alerts via SMS.</div>
                </div>

                <div class="space-y-4">
                    @php
                        $smsPrefs = $prefs['sms'] ?? [];
                    @endphp

                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Critical Alerts Only</div>
                            <div class="text-xs muted">High-priority operational issues</div>
                        </div>
                        <input type="checkbox" name="sms[critical]" value="1" {{ isset($smsPrefs['critical']) && $smsPrefs['critical'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>

                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Security Alerts</div>
                            <div class="text-xs muted">Login attempts and security events</div>
                        </div>
                        <input type="checkbox" name="sms[security]" value="1" {{ isset($smsPrefs['security']) && $smsPrefs['security'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>
            </div>

            {{-- Quiet Hours --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">Quiet Hours</div>
                    <div class="muted text-xs">Don't send non-critical notifications during these hours.</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="quiet_start" class="block text-sm font-medium mb-2">Start Time</label>
                        <input type="time" 
                               id="quiet_start" 
                               name="quiet_hours[start]" 
                               value="{{ $prefs['quiet_hours']['start'] ?? '22:00' }}"
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    </div>

                    <div>
                        <label for="quiet_end" class="block text-sm font-medium mb-2">End Time</label>
                        <input type="time" 
                               id="quiet_end" 
                               name="quiet_hours[end]" 
                               value="{{ $prefs['quiet_hours']['end'] ?? '08:00' }}"
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    </div>
                </div>
            </div>

            {{-- Notification Frequency --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">Notification Frequency</div>
                    <div class="muted text-xs">How often should we batch non-urgent notifications?</div>
                </div>

                <div class="space-y-3">
                    @php
                        $frequency = $prefs['frequency'] ?? 'immediate';
                    @endphp

                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="frequency" value="immediate" {{ $frequency === 'immediate' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-obsidian-700 border-white/10 focus:ring-2 focus:ring-emerald-500/50">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Immediate</div>
                            <div class="text-xs muted">Receive notifications as they happen</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="frequency" value="hourly" {{ $frequency === 'hourly' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-obsidian-700 border-white/10 focus:ring-2 focus:ring-emerald-500/50">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Hourly Digest</div>
                            <div class="text-xs muted">Batched updates every hour</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="frequency" value="daily" {{ $frequency === 'daily' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-obsidian-700 border-white/10 focus:ring-2 focus:ring-emerald-500/50">
                        <div>
                            <div class="text-sm font-medium group-hover:text-white transition-colors">Daily Digest</div>
                            <div class="text-xs muted">One summary email per day at 8:00 AM</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('branch.dashboard') }}" class="px-5 py-2.5 rounded-lg text-sm font-medium bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
@endsection
