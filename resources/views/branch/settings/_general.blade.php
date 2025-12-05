@php
    $general = $settings['general'] ?? $settings;
@endphp

<form id="generalForm" onsubmit="event.preventDefault(); saveSettings('generalForm', '{{ route('branch.settings.general') }}');">
    @csrf
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Localization -->
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-white">Localization</h3>
                    <p class="text-xs text-slate-400 mt-1">Language, timezone, and regional settings</p>
                </div>
                <span class="px-2 py-1 text-2xs font-medium bg-blue-500/20 text-blue-400 rounded">
                    System: {{ strtoupper($systemLocale) }}
                </span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Preferred Language</label>
                    <select name="preferred_language" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">Inherit from system ({{ strtoupper($systemLocale) }})</option>
                        @foreach($supportedLocales as $locale)
                            <option value="{{ $locale }}" @selected(($general['preferred_language'] ?? null) === $locale)>
                                {{ strtoupper($locale) }} - {{ ['en' => 'English', 'fr' => 'French', 'sw' => 'Swahili'][$locale] ?? $locale }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Timezone</label>
                    <select name="timezone" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">Inherit from system</option>
                        @foreach($timezones as $region => $zones)
                            <optgroup label="{{ $region }}">
                                @foreach($zones as $zone)
                                    <option value="{{ $zone }}" @selected(($general['timezone'] ?? null) === $zone)>{{ $zone }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Currency</label>
                    <select name="currency" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">Inherit from system</option>
                        @foreach($currencies as $code => $name)
                            <option value="{{ $code }}" @selected(($general['currency'] ?? null) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Date Format</label>
                        <select name="date_format" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            <option value="d/m/Y" @selected(($general['date_format'] ?? 'd/m/Y') === 'd/m/Y')>DD/MM/YYYY</option>
                            <option value="m/d/Y" @selected(($general['date_format'] ?? '') === 'm/d/Y')>MM/DD/YYYY</option>
                            <option value="Y-m-d" @selected(($general['date_format'] ?? '') === 'Y-m-d')>YYYY-MM-DD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Time Format</label>
                        <select name="time_format" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            <option value="24" @selected(($general['time_format'] ?? '24') === '24')>24-hour (14:30)</option>
                            <option value="12" @selected(($general['time_format'] ?? '') === '12')>12-hour (2:30 PM)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Identity -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Branch Identity</h3>
                <p class="text-xs text-slate-400 mt-1">Display name and contact information</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Display Name</label>
                    <input type="text" name="display_name" value="{{ $general['display_name'] ?? '' }}" 
                           placeholder="{{ $branch->name }}"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <p class="text-2xs text-slate-500 mt-1">Leave blank to use default branch name</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ $general['contact_email'] ?? '' }}" 
                           placeholder="operations@branch.example.com"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Contact Phone</label>
                    <input type="tel" name="contact_phone" value="{{ $general['contact_phone'] ?? '' }}" 
                           placeholder="+256 700 000 000"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                </div>
            </div>

            <!-- Inheritance Info -->
            <div class="bg-obsidian-700/50 rounded-lg p-4 border border-white/5">
                <div class="flex items-start gap-3">
                    <i class="bi bi-info-circle text-blue-400 mt-0.5"></i>
                    <div class="text-xs text-slate-400">
                        <p class="font-medium text-slate-300 mb-1">Settings Inheritance</p>
                        <p>Values left blank will inherit from system defaults. Branch overrides only apply within this branch context.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-check-lg mr-2"></i>Save General Settings
        </button>
    </div>
</form>
