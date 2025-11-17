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
    
    <!-- Premium CSS -->
    <link href="{{ asset('css/settings-premium.css?v=3') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <!-- Premium Header -->
    <header class="premium-header">
        <div class="container-fluid h-100">
            <div class="d-flex align-items-center justify-content-between h-100">
                <!-- Left side -->
                <div class="d-flex align-items-center">
                    <button class="premium-nav-link" type="button" onclick="toggleSidebar()" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <a href="{{ route('settings.index') }}" class="premium-nav-link" aria-label="Settings home">
                        <i class="fas fa-cog me-2"></i>
                        <span class="d-none d-md-inline">{{ config('app.name', 'Baraka Sanaa') }}</span>
                    </a>
                </div>
                
                <!-- Right side -->
                <div class="d-flex align-items-center">
                    <!-- Quick Save Indicator -->
                    <div id="saveStatus" class="d-none d-md-flex align-items-center me-3">
                        <i class="fas fa-circle text-success" id="saveIndicator" style="font-size: 0.5rem; opacity: 0.6;"></i>
                        <span class="ms-2 small text-white" id="saveText">Saved</span>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <button class="premium-nav-link position-relative" type="button" data-bs-toggle="dropdown" aria-label="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
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
                    <div class="dropdown">
                        <button class="premium-nav-link d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-label="User menu">
                            <div class="user-avatar me-2">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <span class="text-white d-none d-md-inline">
                                {{ auth()->user()->name ?? 'User' }}
                            </span>
                            <i class="fas fa-chevron-down ms-2"></i>
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
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()" aria-hidden="true"></div>
    
    <!-- Premium Sidebar -->
    <nav class="premium-sidebar" id="appSidebar" aria-label="Settings navigation">
        <div class="sidebar-nav">
            <!-- Main Settings Section -->
            <div class="nav-section">
                <div class="nav-section-title">Main Settings</div>
                <ul class="nav flex-column">
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.general') }}" class="premium-nav-link {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                            <i class="fas fa-sliders-h premium-nav-icon"></i>
                            <span>General</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.branding') }}" class="premium-nav-link {{ request()->routeIs('settings.branding') ? 'active' : '' }}">
                            <i class="fas fa-palette premium-nav-icon"></i>
                            <span>Branding</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.operations') }}" class="premium-nav-link {{ request()->routeIs('settings.operations') ? 'active' : '' }}">
                            <i class="fas fa-cogs premium-nav-icon"></i>
                            <span>Operations</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.finance') }}" class="premium-nav-link {{ request()->routeIs('settings.finance') ? 'active' : '' }}">
                            <i class="fas fa-dollar-sign premium-nav-icon"></i>
                            <span>Finance</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.notifications') }}" class="premium-nav-link {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
                            <i class="fas fa-bell premium-nav-icon"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.integrations') }}" class="premium-nav-link {{ request()->routeIs('settings.integrations') ? 'active' : '' }}">
                            <i class="fas fa-plug premium-nav-icon"></i>
                            <span>Integrations</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- System Section -->
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <ul class="nav flex-column">
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.language') }}" class="premium-nav-link {{ request()->routeIs('settings.language') ? 'active' : '' }}">
                            <i class="fas fa-language premium-nav-icon"></i>
                            <span>Language & Translations</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.system') }}" class="premium-nav-link {{ request()->routeIs('settings.system') ? 'active' : '' }}">
                            <i class="fas fa-server premium-nav-icon"></i>
                            <span>System</span>
                        </a>
                    </li>
                    <li class="premium-nav-item">
                        <a href="{{ route('settings.website') }}" class="premium-nav-link {{ request()->routeIs('settings.website') ? 'active' : '' }}">
                            <i class="fas fa-globe premium-nav-icon"></i>
                            <span>Website</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Premium Main Content -->
    <main class="premium-main" id="mainContent">
        <!-- Breadcrumbs -->
        @hasSection('breadcrumbs')
            <div class="premium-breadcrumb premium-fade-in">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        @yield('breadcrumbs')
                    </ol>
                </nav>
            </div>
        @else
            <div class="premium-breadcrumb premium-fade-in">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
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
            <div class="premium-page-header premium-fade-in">
                @yield('page_header')
            </div>
        @endif
        
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show premium-slide-in" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show premium-slide-in" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show premium-slide-in" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show premium-slide-in" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <!-- Validation Errors -->
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show premium-slide-in" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mt-2 mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <!-- Content -->
        <div class="premium-fade-in">
            @yield('content')
        </div>
    </main>
    
    <!-- Toastr Container -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- Premium JavaScript -->
    <script>
        // CSRF Token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Premium Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                mainContent.classList.toggle('sidebar-open');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth < 768) {
                if (!sidebar.contains(event.target) && !toggle?.contains(event.target)) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    mainContent.classList.remove('sidebar-open');
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth >= 768) {
                sidebar.classList.add('show');
                overlay.classList.remove('show');
                mainContent.classList.remove('sidebar-open');
            } else {
                sidebar.classList.remove('show', 'collapsed');
                mainContent.classList.remove('sidebar-collapsed');
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
                element.setAttribute('aria-busy', 'true');
            } else {
                element.classList.remove('loading');
                element.disabled = false;
                element.removeAttribute('aria-busy');
            }
        };
        
        // Save status indicator
        window.updateSaveStatus = function(status) {
            const indicator = document.getElementById('saveIndicator');
            const text = document.getElementById('saveText');
            
            if (!indicator || !text) return;
            
            switch (status) {
                case 'saving':
                    indicator.className = 'fas fa-spinner fa-spin text-warning';
                    text.textContent = 'Saving...';
                    break;
                case 'saved':
                    indicator.className = 'fas fa-check-circle text-success';
                    text.textContent = 'Saved';
                    setTimeout(() => {
                        indicator.style.opacity = '0.6';
                    }, 2000);
                    break;
                case 'error':
                    indicator.className = 'fas fa-exclamation-circle text-danger';
                    text.textContent = 'Error';
                    break;
                default:
                    indicator.className = 'fas fa-circle text-muted';
                    text.textContent = 'Idle';
            }
        };
        
        // Form submission with loading state
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = form.querySelector('[type="submit"]');
                
                updateSaveStatus('saving');
                
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
                        updateSaveStatus('saved');
                        showToast(data.message || 'Settings saved successfully', 'success');
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        }
                    } else {
                        updateSaveStatus('error');
                        showToast(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    updateSaveStatus('error');
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
            const main = document.querySelector('.premium-main');
            if (main) {
                main.classList.add('premium-fade-in');
            }
            
            // Initialize save status
            updateSaveStatus('idle');
        });
    </script>

    @stack('scripts')
</body>
</html>