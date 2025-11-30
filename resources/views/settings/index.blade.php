@php
    $settings = $settings ?? app(\App\Repositories\GeneralSettings\GeneralSettingsInterface::class)->all();
    $preferenceMatrix = $settings->details ?? [];
    
    // System Preferences Categories - macOS Style
    $categories = [
        'system' => [
            'title' => 'System',
            'items' => [
                [
                    'id' => 'general',
                    'label' => 'General',
                    'icon' => 'gear-fill',
                    'color' => 'from-slate-500 to-slate-600',
                    'route' => route('settings.general'),
                    'description' => 'App name, timezone, locale',
                    'badge' => null,
                ],
                [
                    'id' => 'appearance',
                    'label' => 'Appearance',
                    'icon' => 'palette-fill',
                    'color' => 'from-purple-500 to-pink-500',
                    'route' => route('settings.branding'),
                    'description' => 'Colors, themes, branding',
                    'badge' => null,
                ],
                [
                    'id' => 'notifications',
                    'label' => 'Notifications',
                    'icon' => 'bell-fill',
                    'color' => 'from-red-500 to-orange-500',
                    'route' => route('settings.notifications'),
                    'description' => 'Alerts, sounds, badges',
                    'badge' => null,
                ],
                [
                    'id' => 'language',
                    'label' => 'Language & Region',
                    'icon' => 'globe',
                    'color' => 'from-blue-500 to-cyan-500',
                    'route' => route('settings.language'),
                    'description' => 'Translations, formats',
                    'badge' => strtoupper(app()->getLocale()),
                ],
            ],
        ],
        'business' => [
            'title' => 'Business',
            'items' => [
                [
                    'id' => 'finance',
                    'label' => 'Finance & Billing',
                    'icon' => 'currency-dollar',
                    'color' => 'from-green-500 to-emerald-500',
                    'route' => route('settings.finance'),
                    'description' => 'Currency, tax, invoicing',
                    'badge' => $settings->currency ?? 'UGX',
                ],
                [
                    'id' => 'operations',
                    'label' => 'Operations',
                    'icon' => 'boxes',
                    'color' => 'from-amber-500 to-yellow-500',
                    'route' => route('settings.operations'),
                    'description' => 'Shipping, SLAs, automation',
                    'badge' => null,
                ],
                [
                    'id' => 'website',
                    'label' => 'Website & SEO',
                    'icon' => 'window-desktop',
                    'color' => 'from-sky-500 to-blue-500',
                    'route' => route('settings.website'),
                    'description' => 'Landing page, meta tags',
                    'badge' => null,
                ],
                [
                    'id' => 'integrations',
                    'label' => 'Integrations',
                    'icon' => 'plug-fill',
                    'color' => 'from-violet-500 to-purple-500',
                    'route' => route('settings.integrations'),
                    'description' => 'APIs, webhooks, services',
                    'badge' => null,
                ],
            ],
        ],
        'advanced' => [
            'title' => 'Advanced',
            'items' => [
                [
                    'id' => 'security',
                    'label' => 'Security & Privacy',
                    'icon' => 'shield-lock-fill',
                    'color' => 'from-slate-600 to-slate-700',
                    'route' => route('settings.system'),
                    'description' => 'Authentication, sessions',
                    'badge' => null,
                ],
                [
                    'id' => 'storage',
                    'label' => 'Storage & Backups',
                    'icon' => 'hdd-fill',
                    'color' => 'from-orange-500 to-red-500',
                    'route' => route('settings.operations'),
                    'description' => 'Files, backups, cleanup',
                    'badge' => null,
                ],
                [
                    'id' => 'performance',
                    'label' => 'Performance',
                    'icon' => 'speedometer2',
                    'color' => 'from-teal-500 to-green-500',
                    'route' => route('settings.system'),
                    'description' => 'Cache, optimization',
                    'badge' => null,
                ],
                [
                    'id' => 'developer',
                    'label' => 'Developer',
                    'icon' => 'code-slash',
                    'color' => 'from-gray-700 to-gray-800',
                    'route' => route('settings.system'),
                    'description' => 'Debug, logs, tools',
                    'badge' => config('app.debug') ? 'ON' : null,
                ],
            ],
        ],
    ];

    $systemStatus = [
        'environment' => config('app.env'),
        'version' => app()->version(),
        'php' => PHP_VERSION,
        'uptime' => 'Online',
        'maintenance' => app()->isDownForMaintenance(),
    ];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'System Preferences')
@section('header', 'System Preferences')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- macOS-style Header -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 shadow-lg mb-4">
            <i class="bi bi-gear-wide-connected text-4xl text-slate-600 dark:text-slate-300"></i>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">System Preferences</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-2">Configure your application settings and preferences</p>
    </div>

    <!-- Spotlight Search -->
    <div class="max-w-2xl mx-auto mb-10">
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="bi bi-search text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
            </div>
            <input 
                type="search" 
                id="settingsSearch"
                class="block w-full pl-11 pr-4 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 shadow-sm text-base transition-all"
                placeholder="Search settings..."
                autocomplete="off"
            >
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <kbd class="hidden sm:inline-flex items-center px-2 py-1 text-xs text-slate-400 bg-slate-100 dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600">
                    ⌘K
                </kbd>
            </div>
        </div>
    </div>

    <!-- System Status Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 mb-10 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $systemStatus['maintenance'] ? 'bg-amber-400' : 'bg-green-400' }} opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 {{ $systemStatus['maintenance'] ? 'bg-amber-500' : 'bg-green-500' }}"></span>
                    </span>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ $systemStatus['maintenance'] ? 'Maintenance Mode' : 'System Online' }}
                    </span>
                </div>
                <div class="hidden md:flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <i class="bi bi-box text-xs"></i>
                        Laravel {{ $systemStatus['version'] }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i class="bi bi-filetype-php text-xs"></i>
                        PHP {{ $systemStatus['php'] }}
                    </span>
                    <span class="flex items-center gap-1.5 px-2 py-0.5 rounded-full {{ $systemStatus['environment'] === 'production' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                        {{ ucfirst($systemStatus['environment']) }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="clearSystemCache()" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <i class="bi bi-arrow-repeat"></i>
                    <span class="hidden sm:inline">Clear Cache</span>
                </button>
                <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <i class="bi bi-house"></i>
                    <span class="hidden sm:inline">Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Categories Grid -->
    <div class="space-y-10">
        @foreach($categories as $categoryKey => $category)
            <section class="settings-category" data-category="{{ $categoryKey }}">
                <h2 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-4 px-1">
                    {{ $category['title'] }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($category['items'] as $item)
                        <a href="{{ $item['route'] }}" 
                           class="settings-item group relative bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-lg hover:shadow-blue-500/10 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                           data-search="{{ strtolower($item['label'] . ' ' . $item['description']) }}">
                            <!-- Icon -->
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br {{ $item['color'] }} flex items-center justify-center mb-4 shadow-lg group-hover:scale-105 transition-transform duration-200">
                                <i class="bi bi-{{ $item['icon'] }} text-2xl text-white"></i>
                            </div>
                            
                            <!-- Label -->
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ $item['label'] }}
                            </h3>
                            
                            <!-- Description -->
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                {{ $item['description'] }}
                            </p>

                            <!-- Badge -->
                            @if($item['badge'])
                                <span class="absolute top-3 right-3 px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <!-- Quick Actions Footer -->
    <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">
                <span class="font-medium text-slate-700 dark:text-slate-300">{{ config('app.name') }}</span>
                &middot; Last updated: {{ now()->format('M d, Y') }}
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('settings.export') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors">
                    <i class="bi bi-download"></i>
                    Export Settings
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Spotlight Search
    const searchInput = document.getElementById('settingsSearch');
    const settingsItems = document.querySelectorAll('.settings-item');
    const categories = document.querySelectorAll('.settings-category');

    searchInput?.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        
        settingsItems.forEach(item => {
            const searchText = item.dataset.search || '';
            const matches = !query || searchText.includes(query);
            item.style.display = matches ? '' : 'none';
        });

        // Hide empty categories
        categories.forEach(cat => {
            const visibleItems = cat.querySelectorAll('.settings-item:not([style*="display: none"])');
            cat.style.display = visibleItems.length ? '' : 'none';
        });
    });

    // Keyboard shortcut (Cmd+K)
    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            searchInput?.focus();
        }
    });

    // Clear cache function
    function clearSystemCache() {
        const btn = event.target.closest('button');
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i>';

        fetch('{{ route("settings.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message || 'Cache cleared!', data.success ? 'success' : 'error');
        })
        .catch(() => showToast('Failed to clear cache', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
    }
</script>
@endsection
