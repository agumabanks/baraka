<!-- left sidebar -->
<div class="col-12 ">
    <nav class="navbar navbar-expand-lg center-nav transparent navbar-light p-0 fixed-top sidebarNavigation">

        <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start text-bg-dark " tabindex="-1"
            id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">

            <div class="offcanvas-header w-90 ">
                <a class="navbar-brand" href="{{ url('/dashboard') }}">
                    <img src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo.png') }}" class="logo" />
                </a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>

            <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100 w-90 mt-0 pt-0">
                <nav class="navbar navbar-expand-lg navbar-light fixed-top   ">
                    <div class="dropdown lang-dropdown navbar_menus changeLocale mobileLocale m-0 ">
                        @include('backend.partials.language')
                    </div>
                </nav>
                <div class="nav-left-sidebar sidebar-dark navbar-expand-lg ">
                    <ul class="navbar-nav">
                        <li class="nav-divider">
                            {{ __('menus.menu') }}
                        </li>
                        <li class="nav-item ">
                            @if (hasPermission('dashboard_read') == true)
                                <a class="nav-link {{ request()->is('/dashboard*') ? 'active' : '' }}"
                                    href="{{ url('/dashboard') }}"><i class="fa fa-home"></i>{{ __('menus.dashboard') }}</a>
                            @endif
                        </li>
                        @if (hasPermission('delivery_man_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/deliveryman*') ? 'active' : '' }}"
                                    href="{{ route('deliveryman.index') }}"><i
                                        class="fa fa-people-carry"></i>{{ __('menus.deliveryman') }}</a>
                            </li>
                        @endif
                        @if (hasPermission('hub_read') == true || hasPermission('hub_payment_read') == true)
                            <li class="nav-item">
                                <a class="nav-link @navActive(['hubs.*','hub.hub-payment.*','admin.hub.*'])"
                                    href="#" data-bs-toggle="collapse" data-bs-target="#hub-manage" aria-expanded="@navExpanded(['hubs.*','hub.hub-payment.*','admin.hub.*'])"
                                    aria-controls="hub-manage"><i
                                        class="fas fa-warehouse"></i>{{ __('menus.hub_mange') }}</a>
                                <div id="hub-manage" class="collapse submenu @navShow(['hubs.*','hub.hub-payment.*','admin.hub.*'])">
                                    <ul class="nav flex-column">
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
                                    aria-controls="merchant-manage"><i
                                        class="fas fa-users"></i>{{ __('menus.merchant_manage') }}</a>
                                <div id="merchant-manage" class="collapse submenu @navShow(['merchant.*','merchant.manage.payment.*'])">
                                    <ul class="nav flex-column">
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
                                    href="{{ route('todo.index') }}"><i class="fas fa-tasks"></i>{{ __('menus.todo_list') }}</a>
                            </li>
                        @endif


                        @if (hasPermission('support_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link @navActive(['support.*'])"
                                    href="{{ route('support.index') }}"><i class="fa fa-comments"></i>{{ __('menus.support') }}</a>
                            </li>
                        @endif



                        @if (hasPermission('parcel_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link @navActive(['parcel.*'])"
                                    href="{{ route('parcel.index') }}"><i class="fa fa-dolly"></i>{{ __('menus.parcel') }}</a>
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
                                        <i class="fa fa-folder"></i> {{ __($bucket['label_trans_key']) }}
                                    </a>
                                    <div id="bucket-{{ $bucketKey }}" class="collapse submenu">
                                        <ul class="nav flex-column">
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
                                                            <i class="{{ $item['icon'] ?? 'fa fa-folder' }}"></i> {{ __($item['label_trans_key']) }}
                                                        </a>
                                                        <div id="{{ $subId }}" class="collapse submenu @navShow($patterns)">
                                                            <ul class="nav flex-column">
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
                                                                            <i class="{{ $leaf['icon'] ?? 'fa fa-circle' }}"></i> {{ __($leaf['label_trans_key']) }}
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
                                                            <i class="{{ $item['icon'] ?? 'fa fa-circle' }}"></i> {{ __($item['label_trans_key']) }}
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
    

    </nav>


</div>

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
