@extends('branch.layout')

@section('title', 'Branch Settings')

@section('content')
    <div class="glass-panel p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold">Branch Settings</div>
                <p class="muted text-xs">Localization and operational preferences scoped to this branch.</p>
            </div>
            <div class="chip text-2xs">Overrides use branch context</div>
        </div>

        <form method="POST" action="{{ route('branch.settings.save') }}" class="space-y-6">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div class="glass-panel p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold">Localization</div>
                        <span class="pill-soft">System: {{ strtoupper($systemLocale) }}</span>
                    </div>
                    <label class="muted text-xs block">Preferred Language</label>
                    <select name="preferred_language" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="">Inherit ({{ strtoupper($systemLocale) }})</option>
                        @foreach($supportedLocales as $locale)
                            <option value="{{ $locale }}" @selected(($settings['preferred_language'] ?? null) === $locale)>{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                    <label class="muted text-xs block">Timezone</label>
                    <input type="text" name="timezone" value="{{ $settings['timezone'] ?? '' }}" placeholder="Africa/Kampala" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <div class="glass-panel p-4 space-y-3">
                    <div class="text-sm font-semibold">Presentation</div>
                    <label class="muted text-xs block">Display name / label</label>
                    <input type="text" name="display_name" value="{{ $settings['display_name'] ?? '' }}" placeholder="{{ $branch->name }}" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <label class="muted text-xs block">Contact email</label>
                    <input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" placeholder="ops@branch.com" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="glass-panel p-4 space-y-3">
                    <div class="text-sm font-semibold">Operational guardrails</div>
                    <label class="muted text-xs block">SLA threshold (%)</label>
                    <input type="number" name="sla_threshold" value="{{ $settings['sla_threshold'] ?? '' }}" min="0" max="100" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <label class="muted text-xs block">Operating notes</label>
                    <textarea name="operating_notes" rows="3" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">{{ $settings['operating_notes'] ?? '' }}</textarea>
                    <p class="muted text-2xs">Use for cutoff windows, after-hours contacts, or on-call rotations.</p>
                </div>
                <div class="glass-panel p-4 space-y-3">
                    <div class="text-sm font-semibold">Inheritance</div>
                    <p class="muted text-xs">
                        Values left blank will inherit global defaults (currency, language, and runtime toggles). Branch overrides apply only inside this branch context.
                    </p>
                    <div class="glass-panel px-3 py-2 border border-white/10 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="muted">System default locale</span>
                            <span class="chip text-2xs">{{ strtoupper($systemLocale) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="chip px-4 py-2 text-sm">Save branch settings</button>
            </div>
        </form>
    </div>
@endsection
