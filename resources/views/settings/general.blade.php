@extends('settings.layouts.app')

@section('title', 'General Settings')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">General</li>
@endsection

@section('page_header')
    <div class="premium-page-header">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-sliders-h text-primary" style="font-size: 1.5rem;"></i>
                <h1 class="premium-page-title mb-1">General Settings</h1>
            </div>
            <p class="text-muted mb-0">
                Core application identity, runtime controls, and language defaults.
            </p>
        </div>
        <div class="page-actions">
            <button type="submit" form="generalSettingsForm" class="btn btn-primary premium-btn-primary">
                <i class="fas fa-save me-2"></i>Save Settings
            </button>
        </div>
    </div>
@endsection

@section('content')
    @php
        $controlCards = [
            [
                'label' => 'Landing Page Controls',
                'description' => 'Hero content, CTA labels, and promo announcements',
                'icon' => 'bi bi-window',
                'swatch' => 'linear-gradient(135deg,#161616,#383838)',
                'status' => data_get($preferenceMatrix, 'landing.hero_headline', 'Live hero'),
                'meta' => [
                    'CTA' => data_get($preferenceMatrix, 'landing.hero_cta'),
                    'Theme' => data_get($preferenceMatrix, 'landing.use_dark_theme', true) ? 'Dark' : 'Light',
                ],
                'route' => route('settings.website'),
                'secondary_route' => url('/'),
                'tags' => 'landing page hero marketing announcement website'
            ],
            [
                'label' => 'Shipping & Logistics',
                'description' => 'Service levels, carriers and customs requirements',
                'icon' => 'bi bi-box-seam',
                'swatch' => 'linear-gradient(135deg,#101010,#2b2b2b)',
                'status' => data_get($preferenceMatrix, 'shipping.default_sla_hours', 48) . 'h SLA',
                'meta' => [
                    'Carrier' => data_get($preferenceMatrix, 'shipping.preferred_carrier'),
                    'Customs' => data_get($preferenceMatrix, 'shipping.customs_documents', true) ? 'Docs required' : 'Docs optional',
                ],
                'route' => route('settings.operations'),
                'secondary_route' => route('settings.operations') . '#shipping',
                'tags' => 'shipping logistics delivery customs carrier sla'
            ],
            [
                'label' => 'Branding & Identity',
                'description' => 'Logos, palette, typography and UI tone',
                'icon' => 'bi bi-palette2',
                'swatch' => 'linear-gradient(135deg,#1c1c1c,#343434)',
                'status' => config('branding.company_name', 'Default brand'),
                'meta' => [
                    'Primary' => config('branding.primary_color', '#0d6efd'),
                    'Secondary' => config('branding.secondary_color', '#6c757d'),
                ],
                'route' => route('settings.branding'),
                'secondary_route' => null,
                'tags' => 'branding color typography logo identity'
            ],
            [
                'label' => 'Branch Management',
                'description' => 'Regions, approvals and staffing guards',
                'icon' => 'bi bi-diagram-3',
                'swatch' => 'linear-gradient(135deg,#131313,#2e2e2e)',
                'status' => count((array) data_get($preferenceMatrix, 'branch_management.regions_active', [])) . ' regions live',
                'meta' => [
                    'Cadence' => ucfirst(data_get($preferenceMatrix, 'branch_management.review_cadence', 'weekly')),
                    'Managers' => data_get($preferenceMatrix, 'branch_management.require_branch_manager', true) ? 'Required' : 'Optional',
                ],
                'route' => route('settings.operations'),
                'secondary_route' => route('settings.operations') . '#branches',
                'tags' => 'branch management regions hubs approvals staffing'
            ],
        ];

        $systemPulse = [
            ['label' => 'Environment', 'value' => strtoupper(config('app.env')), 'icon' => 'bi bi-hdd-stack'],
            ['label' => 'Timezone', 'value' => config('app.timezone'), 'icon' => 'bi bi-clock'],
            ['label' => 'Locale', 'value' => strtoupper(config('app.locale')), 'icon' => 'bi bi-translate'],
            ['label' => 'Release lane', 'value' => data_get($preferenceMatrix, 'system.maintenance_mode', false) ? 'Maintenance' : 'Live', 'icon' => 'bi bi-activity'],
        ];
    @endphp

    <form id="generalSettingsForm" method="POST" action="{{ route('settings.general.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <div class="premium-card mb-4">
            <div class="premium-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-palette text-primary"></i>
                    <div>
                        <h3 class="premium-card-title mb-1">Application Identity</h3>
                        <p class="premium-card-subtitle mb-0">How Baraka appears across the platform.</p>
                    </div>
                </div>
            </div>
            <div class="premium-card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label premium-form-label" for="app_name">Application Name<span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="app_name"
                            name="app_name"
                            class="form-control premium-form-input @error('app_name') is-invalid @enderror"
                            value="{{ old('app_name', $settings['app_name'] ?? '') }}"
                        >
                        @error('app_name')
                            <div class="form-error d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label premium-form-label" for="app_url">Application URL<span class="text-danger">*</span></label>
                        <input
                            type="url"
                            id="app_url"
                            name="app_url"
                            class="form-control premium-form-input @error('app_url') is-invalid @enderror"
                            value="{{ old('app_url', $settings['app_url'] ?? '') }}"
                        >
                        @error('app_url')
                            <div class="form-error d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label premium-form-label" for="app_timezone">Timezone<span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="app_timezone"
                            name="app_timezone"
                            class="form-control premium-form-input @error('app_timezone') is-invalid @enderror"
                            value="{{ old('app_timezone', $settings['app_timezone'] ?? config('app.timezone')) }}"
                            placeholder="Africa/Kampala"
                        >
                        @error('app_timezone')
                            <div class="form-error d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label premium-form-label" for="app_locale">Interface Language<span class="text-danger">*</span></label>
                        <select id="app_locale" name="app_locale" class="form-select premium-form-input @error('app_locale') is-invalid @enderror">
                            @foreach($locales as $code => $label)
                                <option value="{{ $code }}" {{ old('app_locale', $settings['app_locale'] ?? config('app.locale')) === $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('app_locale')
                            <div class="form-error d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <div class="mt-2">
                            <label class="form-label premium-form-label d-block">Maintenance Mode</label>
                            <x-settings.toggle
                                name="maintenance_mode"
                                label="Take the platform offline"
                                :checked="old('maintenance_mode', $settings['maintenance_mode'] ?? false)"
                                help="Use maintenance mode for deployments."
                                icon="fas fa-tools"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="premium-card mb-4">
            <div class="premium-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-globe text-primary"></i>
                    <div>
                        <h3 class="premium-card-title mb-1">Language &amp; Region</h3>
                        <p class="premium-card-subtitle mb-0">
                            Language defaults plus translation metadata and hints.
                        </p>
                    </div>
                </div>
            </div>
            <div class="premium-card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                    @php
                        $currentLocale = old('app_locale', $settings['app_locale'] ?? config('app.locale'));
                    @endphp
                    @foreach($locales as $code => $label)
                        <span class="badge rounded-pill px-3 py-2 {{ $currentLocale === $code ? 'bg-primary text-white' : 'bg-secondary text-white' }}">
                            {{ $label }}
                            @if($currentLocale === $code)
                                <small class="text-white-50 ms-1">(Default)</small>
                            @endif
                        </span>
                    @endforeach
                </div>
                <div class="border rounded-3 p-3 bg-dark text-white">
                    <strong>Translation Engine</strong>
                    <p class="mb-1 text-muted small">
                        A database-backed key/value store drives every translated string via <code>trans_db()</code>.
                    </p>
                    <p class="mb-0 text-muted small">
                        Update translations directly under Language &amp; Translations, and they will appear instantly without redeploying.
                    </p>
                </div>
            </div>
        </div>

        <div class="premium-card">
            <div class="premium-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-server text-primary"></i>
                    <div>
                        <h3 class="premium-card-title mb-1">Runtime Environment</h3>
                        <p class="premium-card-subtitle mb-0">
                            Diagnostics baked into the general settings so you always know what environment you’re editing.
                        </p>
                    </div>
                </div>
            </div>
            <div class="premium-card-body">
                <div class="row g-4">
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label premium-form-label">Environment</label>
                        <input type="text" class="form-control premium-form-input" value="{{ $settings['app_environment'] ?? config('app.env') }}" disabled>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label premium-form-label">Debug Mode</label>
                        <input type="text" class="form-control premium-form-input" value="{{ ($settings['app_debug'] ?? config('app.debug')) ? 'Enabled' : 'Disabled' }}" disabled>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="premium-card mt-5">
        <div class="premium-card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-command text-primary"></i>
                <div>
                    <h3 class="premium-card-title mb-1">Preference Board</h3>
                    <p class="premium-card-subtitle mb-0">macOS-style overview of Baraka’s landing page, shipping, branding, and branch controls.</p>
                </div>
            </div>
        </div>
        <div class="premium-card-body">
            <div class="preferences-search mb-4">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Filter control board – e.g. “branch approvals”" data-control-search>
            </div>

            <div class="control-center-grid">
                @foreach($controlCards as $card)
                    <div class="control-center-card" data-control-card data-tags="{{ $card['tags'] }}">
                        <div class="card-headline">
                            <div class="icon" style="background: {{ $card['swatch'] }}">
                                <i class="{{ $card['icon'] }}"></i>
                            </div>
                            <div>
                                <h4 class="mb-1">{{ $card['label'] }}</h4>
                                <p class="mb-0">{{ $card['description'] }}</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="preference-chip">{{ $card['status'] }}</span>
                            @foreach($card['meta'] as $metaLabel => $metaValue)
                                <span class="preference-chip">{{ $metaLabel }}: {{ $metaValue }}</span>
                            @endforeach
                        </div>
                        <div class="control-actions">
                            <a href="{{ $card['route'] }}" class="btn btn-dark btn-sm text-white">
                                Manage
                            </a>
                            @if($card['secondary_route'])
                                <a href="{{ $card['secondary_route'] }}" class="btn btn-outline-light btn-sm" target="_blank">
                                    Preview
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <h5 class="fw-semibold text-uppercase text-muted mb-3" style="letter-spacing: .2em;">System Pulse</h5>
                <div class="control-center-grid">
                    @foreach($systemPulse as $pulse)
                        <div class="control-center-card">
                            <div class="card-headline">
                                <div class="icon" style="background: linear-gradient(135deg,#0d0d0d,#2e2e2e);">
                                    <i class="{{ $pulse['icon'] }}"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1">{{ $pulse['label'] }}</h4>
                                    <p class="mb-0">{{ $pulse['value'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.querySelector('[data-control-search]');
            const cards = document.querySelectorAll('[data-control-card]');

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.toLowerCase();
                    cards.forEach(card => {
                        const tags = (card.dataset.tags || '').toLowerCase();
                        card.classList.toggle('is-hidden', query && !tags.includes(query));
                    });
                });
            }
        });
    </script>
@endpush
