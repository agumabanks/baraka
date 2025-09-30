@push('styles')
<style>
    .admin-sidebar .offcanvas-nav {
        background: linear-gradient(165deg, #0f172a 0%, #111827 45%, #0b1220 100%);
        color: #e2e8f0;
        min-height: 100vh;
        border-right: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.45);
    }

    .admin-sidebar .offcanvas-header {
        padding: 1.75rem 1.75rem 1.25rem;
    }

    .admin-sidebar .offcanvas-header .navbar-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        color: #f8fafc;
        text-decoration: none;
    }

    .admin-sidebar .offcanvas-header .logo {
        height: 38px;
        width: auto;
        object-fit: contain;
        filter: drop-shadow(0 4px 12px rgba(15, 23, 42, 0.35));
    }

    .admin-sidebar .offcanvas-header .btn-close {
        background-color: rgba(148, 163, 184, 0.15);
        border-radius: 999px;
        padding: 0.75rem;
        opacity: 1;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .admin-sidebar .offcanvas-header .btn-close:hover,
    .admin-sidebar .offcanvas-header .btn-close:focus {
        background-color: rgba(148, 163, 184, 0.3);
        transform: translateY(-1px);
    }

    .admin-sidebar .offcanvas-body {
        padding: 1.5rem 1.75rem 2.25rem;
        gap: 1.75rem;
    }

    .admin-sidebar .sidebar-utilities {
        background: rgba(15, 23, 42, 0.25);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 18px;
        padding: 0.75rem 1rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .admin-sidebar .language-switcher .dropdown-toggle {
        border: 0;
        background: transparent;
        color: #e2e8f0;
        font-weight: 500;
    }

    .admin-sidebar .sidebar-scroll {
        position: relative;
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .admin-sidebar .sidebar-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .admin-sidebar .sidebar-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .admin-sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.35);
        border-radius: 999px;
    }

    .admin-sidebar .nav-left-sidebar {
        background: rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        padding: 1.5rem;
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 22px 40px rgba(15, 23, 42, 0.35);
    }

    @supports not ((backdrop-filter: blur(10px))) {
        .admin-sidebar .nav-left-sidebar {
            background: rgba(15, 23, 42, 0.9);
        }
    }

    .admin-sidebar .navbar-nav {
        gap: 0.35rem;
    }

    .admin-sidebar .nav-divider {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: rgba(148, 163, 184, 0.8);
        margin-bottom: 0.85rem;
        padding-left: 0.25rem;
    }

    .admin-sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        color: rgba(226, 232, 240, 0.92);
        font-weight: 500;
        padding: 0.65rem 0.9rem;
        border-radius: 16px;
        position: relative;
        transition: color 0.25s ease, background-color 0.25s ease, transform 0.25s ease;
        justify-content: flex-start;
    }

    .admin-sidebar .nav-link > i {
        flex: 0 0 40px;
        height: 40px;
        width: 40px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        background: rgba(30, 41, 59, 0.8);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
        color: inherit;
        transition: background-color 0.25s ease, color 0.25s ease, transform 0.25s ease;
        flex-shrink: 0;
    }

    .admin-sidebar .nav-link:hover,
    .admin-sidebar .nav-link:focus {
        color: #f8fafc;
        background: rgba(59, 130, 246, 0.14);
        transform: translateY(-1px);
    }

    .admin-sidebar .nav-link:hover > i,
    .admin-sidebar .nav-link:focus > i {
        background: rgba(59, 130, 246, 0.22);
        color: #f8fafc;
    }

    .admin-sidebar .nav-link.active {
        background: linear-gradient(135deg, #2563eb 0%, #14b8a6 100%);
        color: #f8fafc;
        box-shadow: 0 16px 32px rgba(37, 99, 235, 0.35);
    }

    .admin-sidebar .nav-link.active > i {
        background: rgba(15, 23, 42, 0.25);
        color: #f8fafc;
        box-shadow: none;
    }

    .admin-sidebar .nav-link[data-bs-toggle="collapse"]::after {
        content: "\f105";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        margin-left: auto;
        transition: transform 0.3s ease;
    }

    .admin-sidebar .nav-link[aria-expanded="true"]::after {
        transform: rotate(90deg);
    }

    .admin-sidebar .submenu {
        border-left: 1px solid rgba(148, 163, 184, 0.2);
        margin-left: 2.55rem;
        padding-left: 1rem;
        padding-top: 0.25rem;
        margin-top: 0.25rem;
    }

    .admin-sidebar .submenu .nav-link {
        font-size: 0.95rem;
        font-weight: 500;
        padding: 0.45rem 0.65rem;
        color: rgba(226, 232, 240, 0.8);
        gap: 0.65rem;
        border-radius: 14px;
    }

    .admin-sidebar .submenu .nav-link > i {
        height: 32px;
        width: 32px;
        border-radius: 12px;
        font-size: 0.85rem;
    }

    .admin-sidebar .submenu .nav-link.active {
        box-shadow: none;
    }

    .admin-sidebar .nav-item + .nav-item {
        margin-top: 0.2rem;
    }

    .admin-sidebar .submenu .nav-item + .nav-item {
        margin-top: 0.15rem;
    }

    .admin-sidebar .nav-link:focus-visible {
        outline: 2px solid rgba(94, 234, 212, 0.65);
        outline-offset: 2px;
    }

    /* Badge Styles */
    .admin-sidebar .nav-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        border-radius: 10px;
        background: var(--color-error-500);
        color: #ffffff;
        margin-left: auto;
        margin-right: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: background-color 0.25s ease, transform 0.25s ease;
    }

    .admin-sidebar .nav-badge--success {
        background: var(--color-success-500);
    }

    .admin-sidebar .nav-badge--warning {
        background: var(--color-warning-500);
        color: #000000;
    }

    .admin-sidebar .nav-badge--info {
        background: var(--color-info-500);
    }

    .admin-sidebar .nav-badge--attention {
        background: var(--color-error-500);
        animation: pulse-attention 2s infinite;
    }

    @keyframes pulse-attention {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.05);
        }
    }

    .admin-sidebar .nav-link:hover .nav-badge,
    .admin-sidebar .nav-link:focus .nav-badge {
        transform: scale(1.05);
    }

    .admin-sidebar .nav-link-text {
        flex: 1;
        text-align: left;
    }

    @media (max-width: 991.98px) {
        .admin-sidebar .offcanvas-nav {
            border-right: 0;
            min-height: 100%;
            box-shadow: none;
        }

        .admin-sidebar .offcanvas-body {
            padding: 1.25rem 1rem 2rem;
        }

        .admin-sidebar .nav-left-sidebar {
            border-radius: 20px;
            padding: 1.25rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-sidebar .nav-link,
        .admin-sidebar .nav-link > i,
        .admin-sidebar .nav-link::after {
            transition: none;
        }
    }
</style>
@endpush

<!-- left sidebar -->
<aside class="admin-sidebar col-12">
    <nav class="navbar navbar-expand-lg center-nav transparent navbar-light p-0 fixed-top sidebarNavigation">

        <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start text-bg-dark" tabindex="-1"
            id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel" role="navigation">

            <div class="offcanvas-header w-90 ">
                <a class="navbar-brand" href="{{ url('/dashboard') }}">
                    <img src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo1.png') }}" class="logo" alt="{{ config('app.name', 'Baraka') }}" />
                </a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>

            <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100 w-90 mt-0 pt-0">
                <div class="sidebar-utilities d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                    <div class="language-switcher dropdown lang-dropdown navbar_menus changeLocale mobileLocale m-0">
                        @include('backend.partials.language')
                    </div>
                </div>
                <div class="sidebar-scroll flex-grow-1 w-100">
                    <div class="nav-left-sidebar sidebar-dark navbar-expand-lg">
                        <ul class="navbar-nav">
                        <li class="nav-divider">
                            {{ __('menus.menu') }}
                        </li>
                        <li class="nav-item ">
                            @if (hasPermission('dashboard_read') == true)
                                <a class="nav-link {{ request()->is('/dashboard*') ? 'active' : '' }}"
                                    href="{{ url('/dashboard') }}">
                                    <i aria-hidden="true" class="fa fa-home"></i>
                                    <span class="nav-link-text">{{ __('menus.dashboard') }}</span>
                                    @php
                                        // SLA alerts badge - show if any SLA breaches today
                                        $slaAlerts = 0; // TODO: Fetch from cache/API
                                    @endphp
                                    @if($slaAlerts > 0)
                                        <span class="nav-badge nav-badge--attention"
                                              aria-label="{{ $slaAlerts }} SLA alerts"
                                              title="{{ $slaAlerts }} SLA alerts today">
                                            {{ $slaAlerts }}
                                        </span>
                                    @endif
                                </a>
                            @endif
                        </li>
                        @if (hasPermission('delivery_man_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/deliveryman*') ? 'active' : '' }}"
                                    href="{{ route('deliveryman.index') }}">
                                    <i aria-hidden="true" class="fa fa-people-carry"></i>
                                    <span class="nav-link-text">{{ __('menus.deliveryman') }}</span>
                                    @php
                                        // Active drivers badge
                                        $activeDrivers = 0; // TODO: Fetch from cache/API
                                    @endphp
                                    @if($activeDrivers > 0)
                                        <span class="nav-badge nav-badge--success"
                                              aria-label="{{ $activeDrivers }} active drivers"
                                              title="{{ $activeDrivers }} active drivers">
                                            {{ $activeDrivers }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('hub_read') == true || hasPermission('hub_payment_read') == true)
                            <li class="nav-item">
                                <a class="nav-link @navActive(['hubs.*','hub.hub-payment.*','admin.hub.*'])"
                                    href="#" data-bs-toggle="collapse" data-bs-target="#hub-manage" aria-expanded="@navExpanded(['hubs.*','hub.hub-payment.*','admin.hub.*'])"
                                    aria-controls="hub-manage">
                                    <i aria-hidden="true" class="fas fa-warehouse"></i>
                                    <span class="nav-link-text">{{ __('menus.hub_mange') }}</span>
                                </a>
                                <div id="hub-manage" class="collapse submenu @navShow(['hubs.*','hub.hub-payment.*','admin.hub.*'])">
                                    <ul class="nav flex-column sidebar-submenu">
                                        @if (hasPermission('hub_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/hubs*', 'admin/hub*') ? 'active' : '' }}"
                                                    href="{{ route('hubs.index') }}">{{ __('menus.hubs') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('hub_payment_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/request/hub/payment*') ? 'active' : '' }}"
                                                    href="{{ route('hub.hub-payment.index') }}">{{ __('menus.payments') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('merchant_read') == true || hasPermission('payment_read') == true)
                            <li class="nav-item">
                                <a class="nav-link @navActive(['merchant.*','merchant.manage.payment.*'])"
                                    href="#" data-bs-toggle="collapse" data-bs-target="#merchant-manage" aria-expanded="@navExpanded(['merchant.*','merchant.manage.payment.*'])"
                                    aria-controls="merchant-manage">
                                    <i aria-hidden="true" class="fas fa-users"></i>
                                    <span class="nav-link-text">{{ __('menus.merchant_manage') }}</span>
                                </a>
                                <div id="merchant-manage" class="collapse submenu @navShow(['merchant.*','merchant.manage.payment.*'])">
                                    <ul class="nav flex-column sidebar-submenu">
                                        @if (hasPermission('merchant_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/merchant*') ? 'active' : '' }}"
                                                    href="{{ route('merchant.index') }}">{{ __('menus.merchants') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('payment_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/payment*') ? 'active' : '' }}"
                                                    href="{{ route('merchant.manage.payment.index') }}">{{ __('menus.payments') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('todo_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link @navActive(['todo.*'])"
                                    href="{{ route('todo.index') }}">
                                    <i aria-hidden="true" class="fas fa-tasks"></i>
                                    <span class="nav-link-text">{{ __('menus.todo_list') }}</span>
                                    @php
                                        // Open todos badge
                                        $openTodos = 0; // TODO: Fetch from cache/API
                                    @endphp
                                    @if($openTodos > 0)
                                        <span class="nav-badge nav-badge--warning"
                                              aria-label="{{ $openTodos }} open tasks"
                                              title="{{ $openTodos }} open tasks">
                                            {{ $openTodos }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        @if (hasPermission('support_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link @navActive(['support.*'])"
                                    href="{{ route('support.index') }}">
                                    <i aria-hidden="true" class="fa fa-comments"></i>
                                    <span class="nav-link-text">{{ __('menus.support') }}</span>
                                    @php
                                        // Urgent tickets badge
                                        $urgentTickets = 0; // TODO: Fetch from cache/API
                                    @endphp
                                    @if($urgentTickets > 0)
                                        <span class="nav-badge nav-badge--attention"
                                              aria-label="{{ $urgentTickets }} urgent tickets"
                                              title="{{ $urgentTickets }} urgent support tickets">
                                            {{ $urgentTickets }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        @if (hasPermission('parcel_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link @navActive(['parcel.*'])"
                                    href="{{ route('parcel.index') }}">
                                    <i aria-hidden="true" class="fa fa-dolly"></i>
                                    <span class="nav-link-text">{{ __('menus.parcel') }}</span>
                                    @php
                                        // Exception parcels badge
                                        $exceptionParcels = 0; // TODO: Fetch from cache/API
                                    @endphp
                                    @if($exceptionParcels > 0)
                                        <span class="nav-badge nav-badge--error"
                                              aria-label="{{ $exceptionParcels }} exception parcels"
                                              title="{{ $exceptionParcels }} parcels requiring attention">
                                            {{ $exceptionParcels }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        {{-- Render new config-driven buckets --}}
                        @php($buckets = config('admin_nav.buckets'))
                        @foreach($buckets as $bucketKey => $bucket)
                            @php($children = $bucket['children'] ?? [])
                            @if(\App\Support\Nav::anyVisible($children))
                                <li class="nav-divider">{{ __($bucket['label_trans_key'] ?? $bucket['label'] ?? '') }}</li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#bucket-{{ $bucketKey }}"
                                       aria-controls="bucket-{{ $bucketKey }}" aria-expanded="false"
                                       tabindex="0" role="button">
                                        <i aria-hidden="true" class="fa fa-folder"></i> {{ __($bucket['label_trans_key']) }}
                                    </a>
                                    <div id="bucket-{{ $bucketKey }}" class="collapse submenu">
                                        <ul class="nav flex-column sidebar-submenu">
                                            @foreach($children as $item)
                                                @php(
                                                    $visible = isset($item['model'])
                                                        ? \Illuminate\Support\Facades\Gate::allows('viewAny', $item['model'])
                                                        : \App\Support\Nav::canShowBySignature($item['permission_check'] ?? null)
                                                )
                                                @if(!$visible)
                                                    @continue
                                                @endif

                                                @php($hasKids = isset($item['children']) && is_array($item['children']) && count($item['children'])>0)

                                                @if($hasKids)
                                                    @php($subId = 'bucket-'.$bucketKey.'-'.$item['key'])
                                                    @php($patterns = $item['active_patterns'] ?? (isset($item['route']) ? [$item['route'].'*'] : []))
                                                    <li class="nav-item">
                                                        <a class="nav-link @navActive($patterns)" href="#" data-bs-toggle="collapse" data-bs-target="#{{ $subId }}"
                                                           aria-controls="{{ $subId }}" aria-expanded="@navExpanded($patterns)">
                                                            <i aria-hidden="true" class="{{ $item['icon'] ?? 'fa fa-folder' }}"></i> {{ __($item['label_trans_key']) }}
                                                        </a>
                                                        <div id="{{ $subId }}" class="collapse submenu @navShow($patterns)">
                                                            <ul class="nav flex-column sidebar-submenu">
                                                                @foreach(($item['children'] ?? []) as $leaf)
                                                                    @php(
                                                                        $leafVisible = isset($leaf['model'])
                                                                            ? \Illuminate\Support\Facades\Gate::allows('viewAny', $leaf['model'])
                                                                            : \App\Support\Nav::canShowBySignature($leaf['permission_check'] ?? null)
                                                                    )
                                                                    @if(!$leafVisible) @continue @endif
                                                                    <li class="nav-item">
                                                                        @php($leafPatterns = $leaf['active_patterns'] ?? (isset($leaf['route']) ? [$leaf['route'].'*'] : []))
                                                                        <a class="nav-link @navActive($leafPatterns)" href="{{ isset($leaf['route']) ? route($leaf['route']) : '#' }}">
                                                                            <i aria-hidden="true" class="{{ $leaf['icon'] ?? 'fa fa-circle' }}"></i> {{ __($leaf['label_trans_key']) }}
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </li>
                                                @else
                                                    <li class="nav-item">
                                                        @php($patterns = $item['active_patterns'] ?? (isset($item['route']) ? [$item['route'].'*'] : []))
                                                        <a class="nav-link @navActive($patterns)" href="{{ isset($item['route']) ? route($item['route']) : '#' }}">
                                                            <i aria-hidden="true" class="{{ $item['icon'] ?? 'fa fa-circle' }}"></i> {{ __($item['label_trans_key']) }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @endif
                        @endforeach

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>


</aside>

<!-- end left sidebar -->

@push('scripts')
<script>
    (function() {
        // Persist bucket state in localStorage: admin.sidebar.bucketState.<name>
        document.addEventListener('DOMContentLoaded', function() {
            const buckets = document.querySelectorAll('[id^="bucket-"]');
            buckets.forEach(function (bucket) {
                const name = bucket.id.replace('bucket-','');
                const key = 'admin.sidebar.bucketState.'+name;
                const saved = localStorage.getItem(key);
                if (saved === 'open') {
                    bucket.classList.add('show');
                    const toggler = document.querySelector('[data-bs-target="#'+bucket.id+'"]');
                    if (toggler) toggler.setAttribute('aria-expanded','true');
                }
                bucket.addEventListener('shown.bs.collapse', function(){ localStorage.setItem(key,'open'); });
                bucket.addEventListener('hidden.bs.collapse', function(){ localStorage.setItem(key,'closed'); });
            });

            // Keyboard: Enter toggles focused bucket toggler
            document.querySelectorAll('a.nav-link[data-bs-toggle="collapse"]').forEach(function(a){
                a.addEventListener('keydown', function(e){
                    if (e.key === 'Enter') { e.preventDefault(); a.click(); }
                })
            });
        });
    })();
</script>
@endpush
