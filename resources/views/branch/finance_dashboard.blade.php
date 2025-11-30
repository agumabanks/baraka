@extends('branch.layout')

@section('title', 'Finance Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-1">Finance Dashboard</h2>
            <p class="text-gray-400">Comprehensive financial reporting and analytics</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('branch.finance.export', ['type' => 'invoices']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-download me-1"></i>Export Invoices
                </a>
                <a href="{{ route('branch.finance.export', ['type' => 'payments']) }}" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-download me-1"></i>Export Payments
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 bg-gray-800 border-gray-700">
        <li class="nav-item">
            <a class="nav-link {{ $view === 'overview' ? 'active' : '' }}" href="{{ route('branch.finance', ['view' => 'overview']) }}">
                <i class="fas fa-chart-line me-2"></i>Overview
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'receivables' ? 'active' : '' }}" href="{{ route('branch.finance', ['view' => 'receivables']) }}">
                <i class="fas fa-money-bill-wave me-2"></i>Receivables
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'collections' ? 'active' : '' }}" href="{{ route('branch.finance', ['view' => 'collections']) }}">
                <i class="fas fa-hand-holding-usd me-2"></i>Collections
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'revenue' ? 'active' : '' }}" href="{{ route('branch.finance', ['view' => 'revenue']) }}">
                <i class="fas fa-chart-bar me-2"></i>Revenue
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'invoices' ? 'active' : '' }}" href="{{ route('branch.finance', ['view' => 'invoices']) }}">
                <i class="fas fa-file-invoice me-2"></i>Invoices
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'payments' ? 'active' : '' }}" href="{{ route('branch.finance', ['view' => 'payments']) }}">
                <i class="fas fa-receipt me-2"></i>Payments
            </a>
        </li>
    </ul>

    @if($view === 'overview')
        @include('branch.finance.overview')
    @elseif($view === 'receivables')
        @include('branch.finance.receivables')
    @elseif($view === 'collections')
        @include('branch.finance.collections')
    @elseif($view === 'revenue')
        @include('branch.finance.revenue')
    @elseif($view === 'invoices')
        @include('branch.finance.invoices')
    @elseif($view === 'payments')
        @include('branch.finance.payments')
    @endif
</div>
@endsection
