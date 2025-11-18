@php
    $preferenceMatrix = $settings->details ?? [];
    $cards = [
        [
            'label' => 'General',
            'description' => 'Identity, language & runtime',
            'icon' => 'bi bi-sliders',
            'swatch' => 'linear-gradient(135deg,#0f0f0f,#353535)',
            'status' => $settings->name ?? config('app.name'),
            'route' => route('settings.general'),
            'tags' => 'general identity language locale timezone maintenance runtime'
        ],
        [
            'label' => 'Branding',
            'description' => 'Logos, palette & typography',
            'icon' => 'bi bi-palette',
            'swatch' => 'linear-gradient(135deg,#292929,#5a5a5a)',
            'status' => config('branding.company_name', 'Default palette'),
            'route' => route('settings.branding'),
            'tags' => 'branding logo colors typography theme hero landing'
        ],
        [
            'label' => 'Landing Page',
            'description' => 'Hero copy & web preferences',
            'icon' => 'bi bi-window',
            'swatch' => 'linear-gradient(135deg,#1d1d1d,#444)',
            'status' => ucfirst(data_get($preferenceMatrix, 'landing.status', 'live')),
            'route' => route('settings.website'),
            'tags' => 'landing website cms marketing hero content page'
        ],
        [
            'label' => 'Shipping & Logistics',
            'description' => 'SLAs, modes & automation',
            'icon' => 'bi bi-boxes',
            'swatch' => 'linear-gradient(135deg,#111,#333)',
            'status' => (data_get($preferenceMatrix, 'shipping.default_sla_hours', 48)) . 'h SLA',
            'route' => route('settings.operations'),
            'tags' => 'shipping logistics delivery fleet courier rate operations'
        ],
        [
            'label' => 'Branch Management',
            'description' => 'Regional hubs & sign-offs',
            'icon' => 'bi bi-diagram-3',
            'swatch' => 'linear-gradient(135deg,#202020,#494949)',
            'status' => count((array) data_get($preferenceMatrix, 'branch_management.regions_active', [])) . ' regions',
            'route' => route('settings.operations'),
            'tags' => 'branch management regions hubs staffing approvals'
        ],
        [
            'label' => 'Operations & Automation',
            'description' => 'Backups, maintenance & jobs',
            'icon' => 'bi bi-gear-wide-connected',
            'swatch' => 'linear-gradient(135deg,#181818,#404040)',
            'status' => data_get($preferenceMatrix, 'operations.auto_generate_tracking_ids', true) ? 'Tracking IDs auto' : 'Manual IDs',
            'route' => route('settings.operations'),
            'tags' => 'operations automation maintenance backups jobs dispatch'
        ],
        [
            'label' => 'Finance & Billing',
            'description' => 'Taxes, invoicing & currency',
            'icon' => 'bi bi-currency-exchange',
            'swatch' => 'linear-gradient(135deg,#0e0e0e,#2e2e2e)',
            'status' => data_get($preferenceMatrix, 'finance.default_tax_rate', 0) . '% tax',
            'route' => route('settings.finance'),
            'tags' => 'finance billing tax invoicing payments currency'
        ],
        [
            'label' => 'Notifications',
            'description' => 'Email, SMS & push channels',
            'icon' => 'bi bi-broadcast',
            'swatch' => 'linear-gradient(135deg,#1a1a1a,#393939)',
            'status' => collect(data_get($preferenceMatrix, 'notifications', []))->filter()->count() . ' channels on',
            'route' => route('settings.notifications'),
            'tags' => 'notifications alert sms email push automation'
        ],
        [
            'label' => 'Integrations',
            'description' => 'Webhooks, BI & API keys',
            'icon' => 'bi bi-plug',
            'swatch' => 'linear-gradient(135deg,#151515,#3b3b3b)',
            'status' => data_get($preferenceMatrix, 'integrations.webhooks_enabled', true) ? 'Hooks live' : 'Hooks paused',
            'route' => route('settings.integrations'),
            'tags' => 'integrations webhook slack zapier api powerbi'
        ],
        [
            'label' => 'Language & Translations',
            'description' => 'Copy, localization & tone',
            'icon' => 'bi bi-translate',
            'swatch' => 'linear-gradient(135deg,#080808,#2c2c2c)',
            'status' => strtoupper(data_get($preferenceMatrix, 'general.locale', app()->getLocale())),
            'route' => route('settings.language'),
            'tags' => 'language localization translations copy product'
        ],
        [
            'label' => 'System',
            'description' => 'Auth, retention & runtime',
            'icon' => 'bi bi-hdd-network',
            'swatch' => 'linear-gradient(135deg,#131313,#393939)',
            'status' => data_get($preferenceMatrix, 'system.maintenance_mode', false) ? 'Maintenance' : 'Live',
            'route' => route('settings.system'),
            'tags' => 'system runtime uptime environment auth retention'
        ],
        [
            'label' => 'Website Controls',
            'description' => 'Footer, CTAs & micros',
            'icon' => 'bi bi-command',
            'swatch' => 'linear-gradient(135deg,#191919,#2f2f2f)',
            'status' => \Illuminate\Support\Str::limit(data_get($preferenceMatrix, 'website.hero_title', 'Landing controls'), 18),
            'route' => route('settings.website'),
            'tags' => 'website controls landing cms marketing hero footer'
        ],
    ];

    $systemSnapshot = [
        'environment' => strtoupper(config('app.env')),
        'timezone' => config('app.timezone'),
        'currency' => $settings->currency ?? 'UGX',
        'maintenance' => data_get($preferenceMatrix, 'system.maintenance_mode', false) ? 'Maintenance window' : 'Live traffic',
    ];

    $quickActions = [
        [
            'label' => 'Launch Maintenance',
            'description' => 'Toggle safe maintenance mode',
            'action' => route('settings.system'),
            'type' => 'link',
        ],
        [
            'label' => 'Clear Cache',
            'description' => 'Flush app + config cache',
            'action' => route('settings.clear-cache'),
            'type' => 'post',
        ],
        [
            'label' => 'Export Settings',
            'description' => 'Download JSON snapshot',
            'action' => route('settings.export'),
            'type' => 'link',
        ],
    ];
@endphp

@extends('settings.layouts.app')

@section('title', 'Preferences Hub')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Preferences</li>
@endsection

@section('page_header')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-command text-primary" style="font-size: 1.5rem;"></i>
                <h1 class="premium-page-title mb-0">Baraka Control Center</h1>
            </div>
            <p class="text-muted mb-0">macOS-inspired system preferences for every Baraka surface.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-up-right me-2"></i>Open Baraka
            </a>
            <a href="{{ route('settings.general') }}" class="btn btn-primary premium-btn-primary">
                <i class="bi bi-sliders me-2"></i>Open General Preference
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="preferences-shell">
        <section class="preferences-spotlight">
            <h2>Spotlight for Settings</h2>
            <p class="spotlight-subtitle">Type to jump straight to any preference pane across logistics, finance, operations, or the marketing site.</p>

            <div class="preferences-search">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Search settings - try “shipping SLA” or “branch approvals”" data-preference-search>
            </div>

            <div class="spotlight-hints">
                <span><kbd>⌘</kbd><kbd>,</kbd> Global preferences</span>
                <span><kbd>⌘</kbd><kbd>F</kbd> Filter cards</span>
                <span>Use arrow keys + ↩ to open</span>
            </div>
        </section>

        <div class="preferences-layout">
            <div class="preferences-grid" id="preferencesGrid">
                @foreach($cards as $card)
                    <a href="{{ $card['route'] }}" class="preference-card" data-preference-card data-tags="{{ $card['tags'] }}">
                        <div class="preference-icon" style="background: {{ $card['swatch'] }}">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="preference-meta">
                            <h3>{{ $card['label'] }}</h3>
                            <p>{{ $card['description'] }}</p>
                            <span class="preference-chip">{{ strtoupper(\Illuminate\Support\Str::slug($card['label'], ' ')) }}</span>
                        </div>
                        <div class="preference-status">
                            <span>{{ $card['status'] }}</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                @endforeach
            </div>

            <aside class="preferences-sidebar">
                <div class="system-snapshot">
                    <strong>Environment</strong>
                    <span>{{ $systemSnapshot['environment'] }}</span>
                </div>
                <div class="system-snapshot">
                    <strong>Timezone</strong>
                    <span>{{ $systemSnapshot['timezone'] }}</span>
                </div>
                <div class="system-snapshot">
                    <strong>Currency</strong>
                    <span>{{ $systemSnapshot['currency'] }}</span>
                </div>
                <div class="system-snapshot">
                    <strong>Status</strong>
                    <span>{{ $systemSnapshot['maintenance'] }}</span>
                </div>

                <h4>Quick Actions</h4>
                <div class="preference-quick-actions">
                    @foreach($quickActions as $action)
                        @if($action['type'] === 'link')
                            <a href="{{ $action['action'] }}" target="{{ \Illuminate\Support\Str::startsWith($action['action'], ['http', 'https']) ? '_blank' : '_self' }}">
                                <span>{{ $action['label'] }}</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <button type="button" data-preference-action="{{ $action['action'] }}">
                                <span>{{ $action['label'] }}</span>
                                <i class="bi bi-zap"></i>
                            </button>
                        @endif
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.querySelector('[data-preference-search]');
            const cards = document.querySelectorAll('[data-preference-card]');

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.toLowerCase();
                    cards.forEach(card => {
                        const tags = (card.dataset.tags || '').toLowerCase();
                        if (!query || tags.includes(query)) {
                            card.classList.remove('is-hidden');
                        } else {
                            card.classList.add('is-hidden');
                        }
                    });
                });
            }

            document.querySelectorAll('[data-preference-action]').forEach(button => {
                button.addEventListener('click', () => {
                    const endpoint = button.dataset.preferenceAction;
                    if (!endpoint) return;

                    fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    }).then(response => response.json())
                        .then(data => {
                            const type = data.success ? 'success' : 'error';
                            showToast(data.message || 'Action completed', type);
                        }).catch(() => {
                            showToast('Unable to run quick action', 'error');
                        });
                });
            });
        });
    </script>
@endpush
