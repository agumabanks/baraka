@extends('frontend.layouts.master')
@section('title')
    {{ __('levels.home') }} | {{ @settings()->name }}
@endsection
@section('content')  
    @include('frontend.section.banner')
    @include('frontend.section.service')
    @include('frontend.section.why_courier')
    @include('frontend.section.pricing')
    @include('frontend.section.achievement')
    @include('frontend.section.partner')
    @include('frontend.section.blogs')
@endsection