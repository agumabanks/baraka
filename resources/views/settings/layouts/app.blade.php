<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Settings') - {{ config('app.name', 'Baraka Sanaa') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Premium Settings CSS (fixed header + sidebar layout) -->
    <link href="{{ asset('css/settings-premium.css?v=3') }}" rel="stylesheet">
    <!-- Settings UX Enhancements (form polish, micro-interactions) -->
    <link href="{{ asset('css/settings-ux-enhancements.css') }}?v=3" rel="stylesheet">
    @stack('styles')
</head>
<body class="theme-mono">
    <!-- Header -->
    <header class="app-header premium-header">
        <div class="container-fluid h-100">
            <div class="d-flex align-items-center justify-content-between h-100">
                <!-- Left side -->
                <div class="d-flex align-items-center">
                    <button class="sidebar-toggle me-3" type="button" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <a href="{{ route('settings.index') }}" class="app-brand">
                        <i class="fas fa-cog me-2"></i>
                        {{ config('app.name', 'Baraka Sanaa') }}
                    </a>
                </div>
                
                <!-- Right side -->
                <div class="d-flex align-items-center">
                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <button class="btn btn-link text-white position-relative" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                3
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><a class="dropdown-item" href="#">System backup completed</a></li>
                            <li><a class="dropdown-item" href="#">New user registered</a></li>
                            <li><a class="dropdown-item" href="#">Database optimization finished</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : url('/dashboard') }}"
                                >
                                    View all
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown user-dropdown">
                        <button class="dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <span class="text-white d-none d-md-inline">
                                {{ auth()->user()->name ?? 'User' }}
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">{{ auth()->user()->name ?? 'User' }}</h6></li>
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : url('/dashboard') }}"
                                >
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('settings.index') }}">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/dashboard') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Sidebar Overlay (for mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <nav class="app-sidebar premium-sidebar" id="appSidebar">
        <div class="sidebar-nav">
            <div class="nav-section-title">Main Settings</div>
            <ul class="nav flex-column">
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.general') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h sidebar-nav-icon"></i>
                        General
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.branding') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.branding') ? 'active' : '' }}">
                        <i class="fas fa-palette sidebar-nav-icon"></i>
                        Branding
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.operations') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.operations') ? 'active' : '' }}">
                        <i class="fas fa-cogs sidebar-nav-icon"></i>
                        Operations
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.finance') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.finance') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign sidebar-nav-icon"></i>
                        Finance
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.notifications') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
                        <i class="fas fa-bell sidebar-nav-icon"></i>
                        Notifications
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.integrations') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.integrations') ? 'active' : '' }}">
                        <i class="fas fa-plug sidebar-nav-icon"></i>
                        Integrations
                    </a>
                </li>
            </ul>
            
            <div class="nav-section-title">System</div>
            <ul class="nav flex-column">
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.language') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.language') ? 'active' : '' }}">
                        <i class="fas fa-language sidebar-nav-icon"></i>
                        Language &amp; Translations
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.system') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.system') ? 'active' : '' }}">
                        <i class="fas fa-server sidebar-nav-icon"></i>
                        System
                    </a>
                </li>
                <li class="sidebar-nav-item premium-nav-item">
                    <a href="{{ route('settings.website') }}" class="sidebar-nav-link premium-nav-link {{ request()->routeIs('settings.website') ? 'active' : '' }}">
                        <i class="fas fa-globe sidebar-nav-icon"></i>
                        Website
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="app-main premium-main">
        <!-- Breadcrumbs -->
        @hasSection('breadcrumbs')
            <div class="breadcrumb-container fade-in">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @yield('breadcrumbs')
                    </ol>
                </nav>
            </div>
        @else
            <div class="breadcrumb-container fade-in">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ url('/dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('settings.index') }}">Settings</a>
                        </li>
                        @yield('breadcrumb_current')
                    </ol>
                </nav>
            </div>
        @endif
        
        <!-- Page Header -->
        @hasSection('page_header')
            <div class="page-header fade-in">
                @yield('page_header')
            </div>
        @endif
        
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade-in" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade-in" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade-in" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade-in" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <!-- Validation Errors -->
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade-in" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mt-2 mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <!-- Content -->
        <div class="fade-in">
            @yield('content')
        </div>
    </main>
    
    <!-- Toastr Container -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // CSRF Token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth < 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth >= 768) {
                sidebar.classList.add('show');
                overlay.classList.remove('show');
            }
        });
        
        // Toastr Configuration
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
        
        // Global helper functions
        window.showToast = function(message, type = 'info') {
            toastr[type](message);
        };
        
        window.confirmAction = function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        };
        
        // Loading state management
        window.setLoading = function(element, loading = true) {
            if (loading) {
                element.classList.add('loading');
                element.disabled = true;
            } else {
                element.classList.remove('loading');
                element.disabled = false;
            }
        };
        
        // Form submission with loading state
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = form.querySelector('[type="submit"]');
                
                if (submitBtn) {
                    setLoading(submitBtn, true);
                }
                
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Operation completed successfully', 'success');
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        }
                    } else {
                        showToast(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An unexpected error occurred', 'error');
                })
                .finally(() => {
                    if (submitBtn) {
                        setLoading(submitBtn, false);
                    }
                });
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Add fade-in animation to content on load
        document.addEventListener('DOMContentLoaded', function() {
            const main = document.querySelector('.app-main');
            if (main) {
                main.classList.add('fade-in');
            }
        });
    </script>

    <!-- Settings UX Enhancements JavaScript -->
    <script src="{{ asset('js/settings-ux-enhancements.js') }}"></script>

    @stack('scripts')
</body>
</html>
