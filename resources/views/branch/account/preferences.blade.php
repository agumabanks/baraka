@extends('branch.layout')

@section('title', 'Account – Preferences')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Preferences</h1>
            <p class="muted text-sm">Customize your language, theme, timezone, and display settings.</p>
        </div>

        <form method="POST" action="{{ route('branch.account.preferences.update') }}">
            @csrf
            @method('PUT')

            {{-- Language & Localization --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">Language & Localization</div>
                    <div class="muted text-xs">Choose your preferred language and regional settings.</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="language" class="block text-sm font-medium mb-2">Language</label>
                        <select id="language" 
                                name="language" 
                                class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                                @disabled(\App\Support\SystemSettings::localizationMode() === 'global')>
                            <option value="en" {{ (auth()->user()->preferred_language ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="fr" {{ (auth()->user()->preferred_language ?? 'en') === 'fr' ? 'selected' : '' }}>Français (French)</option>
                            <option value="sw" {{ (auth()->user()->preferred_language ?? 'en') === 'sw' ? 'selected' : '' }}>Kiswahili (Swahili)</option>
                        </select>
                        @if(\App\Support\SystemSettings::localizationMode() === 'global')
                            <div class="mt-2 text-xs muted">Language is managed globally in Settings → Language.</div>
                        @endif
                    </div>

                    <div>
                        <label for="timezone" class="block text-sm font-medium mb-2">Timezone</label>
                        <select id="timezone" 
                                name="timezone" 
                                class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            @php
                                $userTimezone = auth()->user()->timezone ?? 'Africa/Nairobi';
                                $timezones = [
                                    'Africa/Nairobi' => 'East Africa Time (EAT)',
                                    'Africa/Lagos' => 'West Africa Time (WAT)',
                                    'Africa/Johannesburg' => 'South Africa Time (SAST)',
                                    'Africa/Cairo' => 'Eastern European Time (EET)',
                                    'UTC' => 'UTC (Coordinated Universal Time)',
                                    'Europe/London' => 'GMT (London)',
                                    'Europe/Paris' => 'CET (Paris)',
                                    'America/New_York' => 'EST (New York)',
                                ];
                            @endphp
                            @foreach($timezones as $tz => $label)
                                <option value="{{ $tz }}" {{ $userTimezone === $tz ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Display Preferences --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">Display Preferences</div>
                    <div class="muted text-xs">Customize how dates, times, and numbers are displayed.</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="date_format" class="block text-sm font-medium mb-2">Date Format</label>
                        <select id="date_format" 
                                name="date_format" 
                                class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            @php
                                $dateFormat = auth()->user()->date_format ?? 'Y-m-d';
                            @endphp
                            <option value="Y-m-d" {{ $dateFormat === 'Y-m-d' ? 'selected' : '' }}>2024-11-22 (ISO)</option>
                            <option value="m/d/Y" {{ $dateFormat === 'm/d/Y' ? 'selected' : '' }}>11/22/2024 (US)</option>
                            <option value="d/m/Y" {{ $dateFormat === 'd/m/Y' ? 'selected' : '' }}>22/11/2024 (EU)</option>
                            <option value="d-M-Y" {{ $dateFormat === 'd-M-Y' ? 'selected' : '' }}>22-Nov-2024</option>
                        </select>
                    </div>

                    <div>
                        <label for="time_format" class="block text-sm font-medium mb-2">Time Format</label>
                        <select id="time_format" 
                                name="time_format" 
                                class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            @php
                                $timeFormat = auth()->user()->time_format ?? '24h';
                            @endphp
                            <option value="24h" {{ $timeFormat === '24h' ? 'selected' : '' }}>24-hour (14:30)</option>
                            <option value="12h" {{ $timeFormat === '12h' ? 'selected' : '' }}>12-hour (2:30 PM)</option>
                        </select>
                    </div>

                    <div>
                        <label for="currency_display" class="block text-sm font-medium mb-2">Currency Display</label>
                        <select id="currency_display" 
                                name="currency_display" 
                                class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            @php
                                $currencyDisplay = auth()->user()->currency_display ?? 'symbol';
                            @endphp
                            <option value="symbol" {{ $currencyDisplay === 'symbol' ? 'selected' : '' }}>Symbol ($100.00)</option>
                            <option value="code" {{ $currencyDisplay === 'code' ? 'selected' : '' }}>Code (USD 100.00)</option>
                            <option value="name" {{ $currencyDisplay === 'name' ? 'selected' : '' }}>Name (100.00 US Dollars)</option>
                        </select>
                    </div>

                    <div>
                        <label for="number_format" class="block text-sm font-medium mb-2">Number Format</label>
                        <select id="number_format" 
                                name="number_format" 
                                class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            @php
                                $numberFormat = auth()->user()->number_format ?? '1,234.56';
                            @endphp
                            <option value="1,234.56" {{ $numberFormat === '1,234.56' ? 'selected' : '' }}>1,234.56 (US/UK)</option>
                            <option value="1.234,56" {{ $numberFormat === '1.234,56' ? 'selected' : '' }}>1.234,56 (EU)</option>
                            <option value="1 234.56" {{ $numberFormat === '1 234.56' ? 'selected' : '' }}>1 234.56 (SI)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Theme Preferences --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-1">Theme</div>
                    <div class="muted text-xs">Choose your preferred color scheme.</div>
                </div>

                <div class="space-y-3">
                    @php
                        $theme = auth()->user()->theme ?? 'dark';
                    @endphp

                    <label class="flex items-center gap-4 p-4 border border-white/10 rounded-lg cursor-pointer hover:border-emerald-500/50 transition-colors {{ $theme === 'dark' ? 'bg-emerald-500/5 border-emerald-500/30' : 'bg-white/5' }}">
                        <input type="radio" name="theme" value="dark" {{ $theme === 'dark' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-obsidian-700 border-white/10 focus:ring-2 focus:ring-emerald-500/50">
                        <div class="flex-1">
                            <div class="text-sm font-medium">Dark Mode</div>
                            <div class="text-xs muted">Easier on the eyes in low-light environments</div>
                        </div>
                        <div class="w-12 h-8 rounded bg-gradient-to-br from-slate-900 to-slate-700 border border-white/20"></div>
                    </label>

                    <label class="flex items-center gap-4 p-4 border border-white/10 rounded-lg cursor-pointer hover:border-emerald-500/50 transition-colors {{ $theme === 'light' ? 'bg-emerald-500/5 border-emerald-500/30' : 'bg-white/5' }}">
                        <input type="radio" name="theme" value="light" {{ $theme === 'light' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-obsidian-700 border-white/10 focus:ring-2 focus:ring-emerald-500/50">
                        <div class="flex-1">
                            <div class="text-sm font-medium">Light Mode</div>
                            <div class="text-xs muted">Classic bright interface (Coming Soon)</div>
                        </div>
                        <div class="w-12 h-8 rounded bg-gradient-to-br from-white to-slate-100 border border-slate-300"></div>
                    </label>

                    <label class="flex items-center gap-4 p-4 border border-white/10 rounded-lg cursor-pointer hover:border-emerald-500/50 transition-colors {{ $theme === 'auto' ? 'bg-emerald-500/5 border-emerald-500/30' : 'bg-white/5' }}">
                        <input type="radio" name="theme" value="auto" {{ $theme === 'auto' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-obsidian-700 border-white/10 focus:ring-2 focus:ring-emerald-500/50">
                        <div class="flex-1">
                            <div class="text-sm font-medium">Auto (System)</div>
                            <div class="text-xs muted">Matches your device settings</div>
                        </div>
                        <div class="w-12 h-8 rounded bg-gradient-to-r from-slate-900 via-slate-500 to-white border border-white/20"></div>
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
