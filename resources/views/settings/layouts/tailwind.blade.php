<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Settings') - {{ config('app.name', 'Baraka Sanaa') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Vite Assets (Tailwind CSS) -->
    @vite(['resources/css/settings.css', 'resources/js/settings.js'])

    @stack('styles')
</head>
<body class="h-full antialiased bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <div class="settings-shell">
        <!-- Sidebar -->
        <aside class="settings-sidebar" id="settingsSidebar">
            <!-- Brand -->
            <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
                <a href="{{ url('/dashboard') }}" class="flex items-center gap-3 text-slate-900 dark:text-white">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                        <i class="bi bi-gear-fill text-white"></i>
                    </div>
                    <span class="font-semibold">{{ config('app.name', 'Settings') }}</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-6">
                <!-- Main Settings -->
                <div>
                    <div class="px-3 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        General
                    </div>
                    <div class="space-y-1">
                        <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                            <i class="bi bi-grid nav-icon"></i>
                            <span>Overview</span>
                        </a>
                        <a href="{{ route('settings.general') }}" class="nav-item {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                            <i class="bi bi-sliders nav-icon"></i>
                            <span>General</span>
                        </a>
                        <a href="{{ route('settings.branding') }}" class="nav-item {{ request()->routeIs('settings.branding') ? 'active' : '' }}">
                            <i class="bi bi-palette nav-icon"></i>
                            <span>Branding</span>
                        </a>
                        <a href="{{ route('settings.operations') }}" class="nav-item {{ request()->routeIs('settings.operations') ? 'active' : '' }}">
                            <i class="bi bi-gear-wide-connected nav-icon"></i>
                            <span>Operations</span>
                        </a>
                        <a href="{{ route('settings.finance') }}" class="nav-item {{ request()->routeIs('settings.finance') ? 'active' : '' }}">
                            <i class="bi bi-currency-exchange nav-icon"></i>
                            <span>Finance</span>
                        </a>
                    </div>
                </div>

                <!-- Communication -->
                <div>
                    <div class="px-3 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Communication
                    </div>
                    <div class="space-y-1">
                        <a href="{{ route('settings.notifications') }}" class="nav-item {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
                            <i class="bi bi-bell nav-icon"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="{{ route('settings.integrations') }}" class="nav-item {{ request()->routeIs('settings.integrations') ? 'active' : '' }}">
                            <i class="bi bi-plug nav-icon"></i>
                            <span>Integrations</span>
                        </a>
                    </div>
                </div>

                <!-- System -->
                <div>
                    <div class="px-3 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        System
                    </div>
                    <div class="space-y-1">
                        <a href="{{ route('settings.language') }}" class="nav-item {{ request()->routeIs('settings.language') ? 'active' : '' }}">
                            <i class="bi bi-translate nav-icon"></i>
                            <span>Language</span>
                        </a>
                        <a href="{{ route('settings.system') }}" class="nav-item {{ request()->routeIs('settings.system') ? 'active' : '' }}">
                            <i class="bi bi-hdd-network nav-icon"></i>
                            <span>System</span>
                        </a>
                        <a href="{{ route('settings.website') }}" class="nav-item {{ request()->routeIs('settings.website') ? 'active' : '' }}">
                            <i class="bi bi-globe nav-icon"></i>
                            <span>Website</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-sm font-medium text-slate-600 dark:text-slate-300">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                            {{ auth()->user()->name ?? 'User' }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ auth()->user()->email ?? '' }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Logout">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="settings-main">
            <!-- Header -->
            <header class="settings-header">
                <div class="flex items-center gap-4">
                    <!-- Mobile menu button -->
                    <button type="button" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" onclick="toggleSidebar()">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    
                    <nav class="flex items-center gap-2 text-sm">
                        <a href="{{ url('/dashboard') }}" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                            Dashboard
                        </a>
                        <i class="bi bi-chevron-right text-slate-400 text-xs"></i>
                        <a href="{{ route('settings.index') }}" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                            Settings
                        </a>
                        @hasSection('header')
                            <i class="bi bi-chevron-right text-slate-400 text-xs"></i>
                            <span class="text-slate-900 dark:text-white font-medium">@yield('header')</span>
                        @endif
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Theme Toggle -->
                    <button type="button" id="theme-toggle" class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                        <i class="bi bi-sun-fill hidden dark:block" id="theme-toggle-light-icon"></i>
                        <i class="bi bi-moon-fill block dark:hidden" id="theme-toggle-dark-icon"></i>
                    </button>
                    
                    <!-- Back to Dashboard -->
                    <a href="{{ url('/dashboard') }}" class="btn-secondary text-sm">
                        <i class="bi bi-arrow-left mr-2"></i>
                        Dashboard
                    </a>
                </div>
            </header>

            <!-- Content -->
            <main class="settings-content">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 flex items-center gap-3">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 flex items-center gap-3">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span class="font-medium">Please fix the following errors:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden hidden" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <script>
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('settingsSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('color-theme', 
                    document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                );
            });
        }

        // Initialize theme
        if (localStorage.getItem('color-theme') === 'dark' || 
            (!localStorage.getItem('color-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        // AJAX Form Handler
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                const form = e.target;
                const submitBtn = form.querySelector('[type="submit"]');
                const originalText = submitBtn?.innerHTML;

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Saving...';
                }

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Settings saved successfully!', 'success');
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1000);
                        }
                    } else {
                        showToast(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(() => showToast('An unexpected error occurred', 'error'))
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            }
        });

        // Toast Notification
        function showToast(message, type = 'info') {
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600',
                warning: 'bg-amber-600'
            };
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-white ${colors[type]} shadow-lg transform transition-all duration-300 translate-x-full`;
            toast.innerHTML = `<div class="flex items-center gap-2"><i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'}"></i><span>${message}</span></div>`;
            document.body.appendChild(toast);
            
            requestAnimationFrame(() => toast.classList.remove('translate-x-full'));
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    </script>

    @stack('scripts')
</body>
</html>
