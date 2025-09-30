<!-- navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top admin-navbar">
    <div class="container-fluid">
        <!-- Mobile menu button -->
        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" 
                data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Logo -->
        <a class="navbar-brand" href="{{ url('/dashboard') }}">
            <img src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo1.png') }}" 
                 class="logo" alt="Logo" />
        </a>

        <!-- Desktop Navigation Items -->
        <div class="d-none d-lg-flex ms-auto align-items-center">
            <!-- Language Switcher -->
            <div class="dropdown me-3">
                @include('backend.partials.language')
            </div>
            
            <!-- Dark Mode Toggle -->
            <button id="theme-toggle" class="btn btn-link nav-link me-3 p-0"
                    aria-label="Toggle dark mode" title="Toggle dark mode">
                <i class="fas fa-moon" id="theme-icon"></i>
            </button>
            
            <!-- Enhanced Quick Actions Component -->
            <x-quick-actions class="me-3" />

            <!-- Frontend Link -->
            <a href="{{ url('/') }}" class="nav-link me-3" target="_blank">
                <i class="fas fa-globe"></i>
            </a>

            <!-- Notifications -->
            <div class="dropdown me-3">
                <a class="nav-link position-relative dropdown-toggle" href="#" id="notificationDropdown"
                   data-bs-toggle="dropdown" aria-expanded="false" role="button">
                    <i class="fas fa-bell"></i>
                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">3</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown"
                    aria-labelledby="notificationDropdown">
                    <li><div class="notification-title">Notifications</div></li>
                    <li><div class="notification-list">@include('backend.partials.notification')</div></li>
                </ul>
            </div>

            <!-- Todo Button -->
            @if (hasPermission('todo_create') == true)
                <button class="btn btn-primary btn-sm me-3" data-bs-toggle="modal" 
                        data-bs-target="#todoModal" data-url="{{ route('todo.modal') }}">
                    <i class="fas fa-edit"></i> {{ __('to_do.to_do') }}
                </button>
            @endif

            <!-- User Menu -->
            <div class="dropdown">
                @include('backend.partials.profile_menu')
            </div>
        </div>

        <!-- Mobile Navigation Items -->
        <div class="d-lg-none d-flex align-items-center">
            <!-- Mobile Quick Actions -->
            <x-quick-actions class="me-2" />
            
            <a href="{{ url('/') }}" class="nav-link me-2" target="_blank">
                <i class="fas fa-globe"></i>
            </a>
            
            <div class="dropdown me-2">
                <a class="nav-link" href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="badge bg-danger">3</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><div class="notification-title">Notifications</div></li>
                    <li><div class="notification-list">@include('backend.partials.notification')</div></li>
                </ul>
            </div>

            @if (hasPermission('todo_create') == true)
                <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal"
                        data-bs-target="#todoModal" data-url="{{ route('todo.modal') }}">
                    <i class="fas fa-edit"></i>
                </button>
            @endif

            <div class="dropdown">
                @include('backend.partials.profile_menu')
            </div>
        </div>
    </div>
</nav>

@include('backend.todo.to_do_list')

@push('scripts')
    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const root = document.documentElement;
                const navbar = document.querySelector('.admin-navbar');
                const banner = document.querySelector('[data-impersonation-banner]');

                if (!navbar) {
                    return;
                }

                const updateOffsets = () => {
                    const navHeight = navbar.offsetHeight || 0;
                    const navStyle = window.getComputedStyle(navbar);
                    const isFixed = navStyle.position === 'fixed';
                    const bannerHeight = banner ? (banner.offsetHeight || 0) : 0;
                    const effectiveOffset = (isFixed ? navHeight : 0) + bannerHeight;

                    root.style.setProperty('--admin-navbar-height', navHeight + 'px');
                    root.style.setProperty('--admin-offset-top', effectiveOffset + 'px');
                };

                updateOffsets();

                const observedElements = [navbar];
                if (banner) {
                    observedElements.push(banner);
                }

                if ('ResizeObserver' in window) {
                    const observer = new ResizeObserver(() => updateOffsets());
                    observedElements.forEach((element) => observer.observe(element));
                }

                window.addEventListener('resize', updateOffsets, { passive: true });
                window.addEventListener('load', updateOffsets, { once: true });
            });
        </script>
        
        <!-- Theme Switcher Script -->
        <script src="{{ static_asset('js/theme-switcher.js') }}" defer></script>
        
        <!-- Keyboard Shortcuts Script -->
        <script src="{{ static_asset('js/keyboard-shortcuts.js') }}" defer></script>
    @endonce
@endpush
