<!DOCTYPE html>
<html lang="en" @if(app()->getLocale() == 'ar') dir="rtl"@endif>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ optional(settings())->favicon_image ?? static_asset('images/default/favicon.png') }}" type="image/x-icon">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ static_asset('frontend/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ static_asset('frontend/css/style.css') }}"/> 
    <link rel="stylesheet" href="{{ static_asset('frontend/css/odometer.css') }}"/> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"   /> 
    <link rel="stylesheet" href="{{ static_asset('frontend/css/swiper-bundle.min.css') }}"/>
    <link rel="stylesheet" href="{{ static_asset('backend/vendor') }}/toastr/toastr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com"> 
<link href="https://fonts.googleapis.com/css2?family=Bitter&family=Roboto:wght@400&display=swap" rel="stylesheet">
    @stack('styles') 
    @php
        $generalSettings = settings();
        $primaryColor = $generalSettings->primary_color ?? '#7e0095';
        $textColor = $generalSettings->text_color ?? '#343f52';
    @endphp
    <style>
        :root{
            --bs-primary: {{ $primaryColor }};
            --text-color: {{ $textColor }};
            --h-color: {{ $textColor }};
            --bs-white: #ffffff;
        }
    </style>
</head>
<body>   
    @include('frontend.layouts.navbar')
    @yield('content') 
    @include('frontend.layouts.footer')
    <!-- scripts -->
    <script src="{{ static_asset('frontend/js/jquery.min.js') }}" ></script>
    <script src="{{ static_asset('frontend/js/bootstrap.bundle.min.js') }}" ></script> 
    <script src="{{ static_asset('frontend/js/swiper-bundle.min.js') }}" ></script>
    <script src="{{ static_asset('frontend/js/jquery.odometer.min.js') }}" ></script>
    <script src="{{ static_asset('frontend/js/theme.js') }}" ></script> 
    <script src="{{ static_asset('backend/vendor') }}/toastr/toastr.min.js"></script> 
    {!! Toastr::message() !!}
    @stack('scripts')
   
</body>
</html>
