@include('backend.partials.header')
@include('backend.partials.impersonation_banner')
@if (Auth::user()->user_type == \App\Enums\UserType::MERCHANT)
    @include('backend.merchant_panel.partials.navber')
@else
    @include('backend.partials.navber')
@endif
    <main class="dashboard-ecommerce">
         {{-- Breadcrumb Navigation --}}
         @hasSection('breadcrumb')
           @yield('breadcrumb')
         @else
           @include('components.breadcrumb', ['breadcrumbs' => $breadcrumbs ?? []])
         @endif
      <div class="main-content">
        @yield('maincontent')
@include('backend.partials.dynamic-modal')
@include('backend.partials.footer_text')
@include('backend.partials.footer')
