@extends('admin.layout')

@section('title', trans_db('admin.dashboard.title', [], null, 'Dashboard'))
@section('header', trans_db('admin.dashboard.header', [], null, 'Dashboard Overview'))

@section('content')
@php
    $defaultCurrency = $defaultCurrency ?? \App\Support\SystemSettings::defaultCurrency();
    $formatCurrency = $formatCurrency ?? fn($amount, ?string $currency = null) => \App\Support\SystemSettings::formatCurrency((float) $amount, $currency);
    $brandName = $branding['company_name'] ?? \App\Support\SystemSettings::companyName();
@endphp
<div id="dashboard-page">
	    {{-- Welcome Banner --}}
	    <div class="glass-panel p-6 mb-6 bg-gradient-to-r from-sky-500/10 via-purple-500/10 to-emerald-500/10">
	        <div class="flex items-center justify-between">
	            <div>
	                <h2 class="text-xl font-semibold mb-1">{{ trans_db('admin.dashboard.welcome', ['name' => auth()->user()->name], null, 'Welcome back, :name') }}</h2>
	                <p class="text-slate-400 text-sm">{{ trans_db('admin.dashboard.subtitle', ['brand' => $brandName], null, "Here's what's happening across :brand today.") }}</p>
	            </div>
	            <div class="hidden md:flex items-center gap-3">
	                <div class="text-right">
	                    <div class="text-2xs uppercase muted">{{ trans_db('admin.dashboard.current_time', [], null, 'Current Time') }}</div>
	                    <div class="text-lg font-mono" id="current-time">{{ now()->format('H:i') }}</div>
	                </div>
	                <div class="w-px h-10 bg-white/10"></div>
	                <div class="text-right">
	                    <div class="text-2xs uppercase muted">{{ trans_db('admin.dashboard.date', [], null, 'Date') }}</div>
	                    <div class="text-sm">{{ now()->locale(app()->getLocale())->translatedFormat('D, M d Y') }}</div>
	                </div>
	            </div>
	        </div>
	    </div>

    {{-- Date Range Filter --}}
	    <div class="glass-panel p-4 mb-6">
	        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-3">
	            <div class="flex gap-2">
	                <button type="submit" name="range" value="today" class="btn btn-sm {{ request('range', '7d') === 'today' ? 'btn-primary' : 'btn-secondary' }}">{{ trans_db('admin.dashboard.range.today', [], null, 'Today') }}</button>
	                <button type="submit" name="range" value="7d" class="btn btn-sm {{ request('range', '7d') === '7d' ? 'btn-primary' : 'btn-secondary' }}">{{ trans_db('admin.dashboard.range.7d', [], null, '7 Days') }}</button>
	                <button type="submit" name="range" value="30d" class="btn btn-sm {{ request('range') === '30d' ? 'btn-primary' : 'btn-secondary' }}">{{ trans_db('admin.dashboard.range.30d', [], null, '30 Days') }}</button>
	                <button type="submit" name="range" value="this_month" class="btn btn-sm {{ request('range') === 'this_month' ? 'btn-primary' : 'btn-secondary' }}">{{ trans_db('admin.dashboard.range.this_month', [], null, 'This Month') }}</button>
	            </div>
	            <div class="flex-1 text-right">
	                <span class="text-sm muted">{{ $dateRange['label'] ?? trans_db('admin.dashboard.range.last_7_days', [], null, 'Last 7 Days') }}</span>
	            </div>
	        </form>
	    </div>

    {{-- Primary KPIs --}}
	    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
	        @include('admin.components.kpi-card', [
	            'title' => trans_db('admin.dashboard.kpi.total_shipments.title', [], null, 'Total Shipments'),
	            'value' => number_format($stats['total_shipments'] ?? 0),
	            'subtitle' => trans_db('admin.dashboard.kpi.this_period', [], null, 'This period'),
	            'icon' => 'box',
	            'color' => 'sky',
	            'trend' => $stats['shipment_trend'] ?? null,
	            'href' => route('admin.shipments.index'),
	        ])
	        @include('admin.components.kpi-card', [
	            'title' => trans_db('admin.dashboard.kpi.delivered.title', [], null, 'Delivered'),
	            'value' => number_format($stats['delivered'] ?? 0),
	            'subtitle' => trans_db('admin.dashboard.kpi.delivered.subtitle', ['rate' => ($stats['delivery_rate'] ?? 0)], null, ':rate% success rate'),
	            'icon' => 'check',
	            'color' => 'emerald',
	            'href' => route('admin.shipments.index', ['status' => 'delivered']),
	        ])
	        @include('admin.components.kpi-card', [
	            'title' => trans_db('admin.dashboard.kpi.in_transit.title', [], null, 'In Transit'),
	            'value' => number_format($stats['in_transit'] ?? 0),
	            'subtitle' => trans_db('admin.dashboard.kpi.in_transit.subtitle', [], null, 'Active shipments'),
	            'icon' => 'truck',
	            'color' => 'purple',
	            'href' => route('admin.tracking.dashboard'),
	        ])
	        @include('admin.components.kpi-card', [
	            'title' => trans_db('admin.dashboard.kpi.revenue.title', [], null, 'Revenue'),
	            'value' => $formatCurrency($stats['revenue'] ?? 0, $defaultCurrency),
	            'subtitle' => trans_db('admin.dashboard.kpi.this_period', [], null, 'This period'),
	            'icon' => 'currency',
	            'color' => 'amber',
	            'trend' => $stats['revenue_trend'] ?? null,
	            'href' => route('admin.finance.dashboard'),
	        ])
	    </div>

    {{-- System Alerts (if any) --}}
    @if(!empty($alerts))
    <div class="mb-6 space-y-2">
        @foreach($alerts as $alert)
            <div class="glass-panel p-4 border-l-4 {{ $alert['type'] === 'danger' ? 'border-red-500 bg-red-500/5' : ($alert['type'] === 'warning' ? 'border-amber-500 bg-amber-500/5' : 'border-sky-500 bg-sky-500/5') }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($alert['type'] === 'danger')
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        @elseif($alert['type'] === 'warning')
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @else
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @endif
                        <div>
                            <div class="font-medium">{{ $alert['title'] }}</div>
                            <div class="text-sm muted">{{ $alert['message'] }}</div>
                        </div>
                    </div>
                    <span class="badge {{ $alert['type'] === 'danger' ? 'badge-danger' : ($alert['type'] === 'warning' ? 'badge-warn' : 'badge-primary') }}">{{ $alert['count'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
    @endif

	    {{-- SLA & Performance Metrics --}}
	    <div class="grid gap-4 md:grid-cols-5 mb-6">
	        <div class="stat-card border-l-2 border-emerald-500">
	            <div class="muted text-xs uppercase mb-1">{{ trans_db('admin.dashboard.sla.on_time_delivery', [], null, 'On-Time Delivery') }}</div>
	            <div class="text-2xl font-bold text-emerald-400">{{ $slaMetrics['on_time_rate'] ?? 0 }}%</div>
	            <div class="text-xs muted">{{ trans_db('admin.dashboard.common.of', ['count' => ($slaMetrics['on_time_delivered'] ?? 0), 'total' => ($slaMetrics['total_delivered'] ?? 0)], null, ':count of :total') }}</div>
	        </div>
	        <div class="stat-card border-l-2 border-sky-500">
	            <div class="muted text-xs uppercase mb-1">{{ trans_db('admin.dashboard.sla.avg_delivery_time', [], null, 'Avg Delivery Time') }}</div>
	            <div class="text-2xl font-bold">{{ $slaMetrics['avg_delivery_time'] ?? 0 }}h</div>
	            <div class="text-xs muted">{{ trans_db('admin.dashboard.sla.hours_from_pickup', [], null, 'Hours from pickup') }}</div>
	        </div>
	        <div class="stat-card border-l-2 border-purple-500">
	            <div class="muted text-xs uppercase mb-1">{{ trans_db('admin.dashboard.sla.first_attempt_rate', [], null, 'First Attempt Rate') }}</div>
	            <div class="text-2xl font-bold text-purple-400">{{ $slaMetrics['first_attempt_rate'] ?? 0 }}%</div>
	            <div class="text-xs muted">{{ trans_db('admin.dashboard.sla.delivered_first_try', [], null, 'Delivered on 1st try') }}</div>
	        </div>
	        <div class="stat-card border-l-2 border-amber-500">
	            <div class="muted text-xs uppercase mb-1">{{ trans_db('admin.dashboard.sla.exceptions', [], null, 'Exceptions') }}</div>
	            <div class="text-2xl font-bold text-amber-400">{{ $slaMetrics['exceptions'] ?? 0 }}</div>
	            <div class="text-xs muted">{{ trans_db('admin.dashboard.sla.rate', ['rate' => ($slaMetrics['exception_rate'] ?? 0)], null, ':rate% rate') }}</div>
	        </div>
	        <div class="stat-card border-l-2 border-rose-500">
	            <div class="muted text-xs uppercase mb-1">{{ trans_db('admin.dashboard.sla.returns', [], null, 'Returns') }}</div>
	            <div class="text-2xl font-bold text-rose-400">{{ $slaMetrics['returns'] ?? 0 }}</div>
	            <div class="text-xs muted">{{ trans_db('admin.dashboard.sla.cancelled', ['count' => ($slaMetrics['cancelled'] ?? 0)], null, '+ :count cancelled') }}</div>
	        </div>
	    </div>

    {{-- COD & Finance Overview --}}
	    <div class="grid gap-4 md:grid-cols-3 mb-6">
	        <div class="glass-panel p-5">
	            <div class="flex items-center justify-between mb-4">
	                <h4 class="font-semibold">{{ trans_db('admin.dashboard.finance.cod_collections', [], null, 'COD Collections') }}</h4>
	                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
	            </div>
	            <div class="space-y-3">
	                <div class="flex justify-between items-center">
	                    <span class="text-sm muted">{{ trans_db('admin.common.collected', [], null, 'Collected') }}</span>
	                    <span class="font-bold text-emerald-400">{{ $formatCurrency($financeOverview['cod_collected'] ?? 0, $defaultCurrency) }}</span>
	                </div>
	                <div class="flex justify-between items-center">
	                    <span class="text-sm muted">{{ trans_db('admin.common.pending', [], null, 'Pending') }}</span>
	                    <span class="font-bold text-amber-400">{{ $formatCurrency($financeOverview['cod_pending'] ?? 0, $defaultCurrency) }}</span>
	                </div>
	            </div>
	        </div>
	        <div class="glass-panel p-5">
	            <div class="flex items-center justify-between mb-4">
	                <h4 class="font-semibold">{{ trans_db('admin.dashboard.finance.settlements', [], null, 'Settlements') }}</h4>
	                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
	            </div>
	            <div class="space-y-3">
	                <div class="flex justify-between items-center">
	                    <span class="text-sm muted">{{ trans_db('admin.common.completed', [], null, 'Completed') }}</span>
	                    <span class="font-bold text-emerald-400">{{ $formatCurrency($financeOverview['settlements_completed'] ?? 0, $defaultCurrency) }}</span>
	                </div>
	                <div class="flex justify-between items-center">
	                    <span class="text-sm muted">{{ trans_db('admin.common.pending', [], null, 'Pending') }}</span>
	                    <span class="font-bold text-amber-400">{{ $formatCurrency($financeOverview['settlements_pending'] ?? 0, $defaultCurrency) }}</span>
	                </div>
	            </div>
	        </div>
	        <div class="glass-panel p-5">
	            <div class="flex items-center justify-between mb-4">
	                <h4 class="font-semibold">{{ trans_db('admin.dashboard.finance.invoices', [], null, 'Invoices') }}</h4>
	                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
	            </div>
	            <div class="space-y-3">
	                <div class="flex justify-between items-center">
	                    <span class="text-sm muted">{{ trans_db('admin.common.pending', [], null, 'Pending') }}</span>
	                    <span class="font-bold">{{ $formatCurrency($financeOverview['invoices_pending'] ?? 0, $defaultCurrency) }}</span>
	                </div>
	                <div class="flex justify-between items-center">
	                    <span class="text-sm muted">{{ trans_db('admin.common.overdue', [], null, 'Overdue') }}</span>
	                    <span class="font-bold text-rose-400">{{ $formatCurrency($financeOverview['invoices_overdue'] ?? 0, $defaultCurrency) }}</span>
	                </div>
	            </div>
	        </div>
	    </div>

    {{-- Secondary Stats Row --}}
    <div class="grid gap-4 md:grid-cols-5 mb-6">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">{{ trans_db('admin.dashboard.stats.branches', [], null, 'Branches') }}</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['active_branches'] ?? $stats['total_branches'] ?? 0) }}</div>
                    <div class="text-xs muted">{{ trans_db('admin.dashboard.stats.of_total', ['total' => number_format($stats['total_branches'] ?? 0)], null, 'of :total total') }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">{{ trans_db('admin.dashboard.stats.drivers', [], null, 'Drivers') }}</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['active_drivers'] ?? 0) }}</div>
                    <div class="text-xs muted">{{ trans_db('admin.dashboard.stats.avg_per_driver', ['avg' => ($operationsMetrics['driver_metrics']['avg_deliveries_per_driver'] ?? 0)], null, ':avg avg/driver') }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">{{ trans_db('admin.dashboard.stats.merchants', [], null, 'Merchants') }}</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['total_merchants'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">{{ trans_db('admin.dashboard.stats.customers', [], null, 'Customers') }}</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['total_customers'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">{{ trans_db('admin.dashboard.stats.scans_today', [], null, 'Scans Today') }}</div>
                    <div class="text-2xl font-bold">{{ number_format($operationsMetrics['scans_today'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Operations Snapshot --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="stat-card bg-gradient-to-br from-sky-500/10 to-sky-500/5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-sky-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $operationsMetrics['pending_pickups'] ?? 0 }}</div>
                    <div class="text-sm muted">{{ trans_db('admin.dashboard.ops.pending_pickups', [], null, 'Pending Pickups') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-br from-purple-500/10 to-purple-500/5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $operationsMetrics['out_for_delivery'] ?? 0 }}</div>
                    <div class="text-sm muted">{{ trans_db('admin.dashboard.ops.out_for_delivery', [], null, 'Out for Delivery') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-br from-emerald-500/10 to-emerald-500/5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $operationsMetrics['fleet_status']['active'] ?? 0 }}</div>
                    <div class="text-sm muted">{{ trans_db('admin.dashboard.ops.active_vehicles', [], null, 'Active Vehicles') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-br from-amber-500/10 to-amber-500/5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $operationsMetrics['fleet_status']['maintenance'] ?? 0 }}</div>
                    <div class="text-sm muted">{{ trans_db('admin.dashboard.ops.in_maintenance', [], null, 'In Maintenance') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts and Activity Row --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        {{-- Shipment Trends Chart --}}
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ trans_db('admin.dashboard.charts.shipment_trends', [], null, 'Shipment Trends') }}</h3>
                <div class="flex items-center gap-4 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-sky-500"></span>
                        <span class="muted">{{ trans_db('admin.dashboard.charts.created', [], null, 'Created') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="muted">{{ trans_db('admin.dashboard.charts.delivered', [], null, 'Delivered') }}</span>
                    </div>
                </div>
            </div>
            <div class="h-64">
                <canvas id="shipmentsChart"></canvas>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">{{ trans_db('admin.dashboard.charts.status_distribution', [], null, 'Status Distribution') }}</h3>
            <div class="h-48 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($stats['status_breakdown'] ?? [] as $status => $count)
                    @php
                        $statusColors = [
                            'pending' => 'bg-slate-500',
                            'processing' => 'bg-sky-500',
                            'in_transit' => 'bg-purple-500',
                            'out_for_delivery' => 'bg-amber-500',
                            'delivered' => 'bg-emerald-500',
                            'returned' => 'bg-rose-500',
                        ];
                    @endphp
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $statusColors[$status] ?? 'bg-slate-500' }}"></span>
                            <span class="capitalize">{{ str_replace('_', ' ', $status) }}</span>
                        </div>
                        <span class="muted">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Quick Actions & Recent Activity --}}
    <div class="grid gap-6 lg:grid-cols-2 mb-6">
        {{-- Quick Actions --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">{{ trans_db('admin.dashboard.quick_actions.title', [], null, 'Quick Actions') }}</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('admin.pos.index') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">{{ trans_db('admin.dashboard.quick_actions.new_shipment', [], null, 'New Shipment') }}</div>
                        <div class="text-xs muted">{{ trans_db('admin.dashboard.quick_actions.shipment_pos', [], null, 'Shipment POS') }}</div>
                    </div>
                </a>
                <a href="{{ route('admin.tracking.dashboard') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">{{ trans_db('admin.dashboard.quick_actions.live_tracking', [], null, 'Live Tracking') }}</div>
                        <div class="text-xs muted">{{ trans_db('admin.dashboard.quick_actions.monitor_fleet', [], null, 'Monitor fleet') }}</div>
                    </div>
                </a>
                <a href="{{ route('admin.analytics.dashboard') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">{{ trans_db('admin.dashboard.quick_actions.analytics', [], null, 'Analytics') }}</div>
                        <div class="text-xs muted">{{ trans_db('admin.dashboard.quick_actions.view_reports', [], null, 'View reports') }}</div>
                    </div>
                </a>
                <a href="{{ route('admin.dispatch.index') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">{{ trans_db('admin.dashboard.quick_actions.dispatch', [], null, 'Dispatch') }}</div>
                        <div class="text-xs muted">{{ trans_db('admin.dashboard.quick_actions.route_optimization', [], null, 'Route optimization') }}</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ trans_db('admin.dashboard.recent_activity.title', [], null, 'Recent Activity') }}</h3>
                <a href="{{ route('admin.security.dashboard') }}" class="text-xs text-sky-400 hover:text-sky-300">{{ trans_db('admin.common.view_all', [], null, 'View All') }}</a>
            </div>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($recentActivity ?? [] as $activity)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-white/5">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0">
                            @if(str_contains($activity['action'] ?? '', 'shipment'))
                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4"></path></svg>
                            @elseif(str_contains($activity['action'] ?? '', 'login'))
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            @else
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm">{{ $activity['description'] ?? $activity['action'] ?? 'Activity' }}</div>
                            <div class="text-xs muted mt-1">
                                {{ $activity['user'] ?? 'System' }} &bull; {{ \Carbon\Carbon::parse($activity['created_at'] ?? now())->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 muted">
                        <svg class="w-8 h-8 mx-auto mb-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>No recent activity</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top Performers & Geographic Data --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        {{-- Top Branches --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">{{ trans_db('admin.dashboard.top_branches.title', [], null, 'Top Branches') }}</h3>
            <div class="space-y-3">
                @forelse($topPerformers['top_branches'] ?? [] as $index => $branch)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-white/5">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-500/20 text-amber-400' : 'bg-white/10' }} flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</span>
                            <div>
                                <div class="font-medium">{{ $branch['name'] ?? trans_db('admin.common.unknown', [], null, 'Unknown') }}</div>
                                <div class="text-xs muted">{{ $branch['code'] ?? '' }}</div>
                            </div>
                        </div>
                        <span class="text-sm font-bold">{{ number_format($branch['shipments'] ?? 0) }}</span>
                    </div>
                @empty
                    <div class="text-center py-4 muted text-sm">{{ trans_db('admin.common.no_data', [], null, 'No data available') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">{{ trans_db('admin.dashboard.top_customers.title', [], null, 'Top Customers') }}</h3>
            <div class="space-y-3">
                @forelse($topPerformers['top_customers'] ?? [] as $index => $customer)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-white/5">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-white/10' }} flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</span>
                            <div>
                                <div class="font-medium truncate" style="max-width: 120px;">{{ $customer['name'] ?? trans_db('admin.common.unknown', [], null, 'Unknown') }}</div>
                                <div class="text-xs muted">{{ trans_db('admin.dashboard.top_customers.shipments', ['count' => number_format($customer['shipments'] ?? 0)], null, ':count shipments') }}</div>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-emerald-400">{{ $formatCurrency($customer['revenue'] ?? 0, $defaultCurrency) }}</span>
                    </div>
                @empty
                    <div class="text-center py-4 muted text-sm">{{ trans_db('admin.common.no_data', [], null, 'No data available') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Geographic Distribution --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">{{ trans_db('admin.dashboard.shipments_by_city.title', [], null, 'Shipments by City') }}</h3>
            <div class="space-y-2">
                @forelse($geographicData['by_city'] ?? [] as $city)
                    @php 
                        $maxCount = max(array_column($geographicData['by_city'] ?? [['count' => 1]], 'count'));
                        $percentage = $maxCount > 0 ? ($city['count'] / $maxCount) * 100 : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span>{{ $city['city'] ?? trans_db('admin.common.unknown', [], null, 'Unknown') }}</span>
                            <span class="muted">{{ number_format($city['count']) }}</span>
                        </div>
                        <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-500 to-purple-500 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 muted text-sm">{{ trans_db('admin.common.no_data', [], null, 'No data available') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top Routes --}}
    @if(!empty($topPerformers['top_routes']))
    <div class="glass-panel p-5 mb-6">
        <h3 class="text-lg font-semibold mb-4">{{ trans_db('admin.dashboard.top_routes.title', [], null, 'Top Routes') }}</h3>
        <div class="grid gap-3 md:grid-cols-5">
            @foreach($topPerformers['top_routes'] as $index => $route)
                <div class="p-4 rounded-lg bg-gradient-to-br from-white/5 to-white/0 border border-white/10">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-5 h-5 rounded-full {{ $index === 0 ? 'bg-amber-500/30 text-amber-400' : 'bg-white/10' }} flex items-center justify-center text-xs font-bold">#{{ $index + 1 }}</span>
                    </div>
                    <div class="text-sm font-medium mb-1">{{ $route['route'] ?? trans_db('admin.common.unknown', [], null, 'Unknown') }}</div>
                    <div class="text-2xl font-bold text-sky-400">{{ number_format($route['volume'] ?? 0) }}</div>
                    <div class="text-xs muted">{{ trans_db('admin.dashboard.top_routes.shipments', [], null, 'shipments') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent Shipments Table --}}
    <div class="glass-panel p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ trans_db('admin.dashboard.recent_shipments.title', [], null, 'Recent Shipments') }}</h3>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-sm btn-secondary">{{ trans_db('admin.common.view_all', [], null, 'View All') }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="dhl-table">
                <thead>
                    <tr>
                        <th class="text-left">{{ trans_db('admin.dashboard.recent_shipments.tracking', [], null, 'Tracking #') }}</th>
                        <th class="text-left">{{ trans_db('admin.dashboard.recent_shipments.origin', [], null, 'Origin') }}</th>
                        <th class="text-left">{{ trans_db('admin.dashboard.recent_shipments.destination', [], null, 'Destination') }}</th>
                        <th class="text-left">{{ trans_db('admin.dashboard.recent_shipments.status', [], null, 'Status') }}</th>
                        <th class="text-left">{{ trans_db('admin.dashboard.recent_shipments.created', [], null, 'Created') }}</th>
                        <th class="text-right">{{ trans_db('admin.common.actions', [], null, 'Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentShipments ?? [] as $shipment)
                        <tr class="hover:bg-white/5">
                            <td class="font-mono text-sky-400">{{ $shipment->tracking_number ?? '#' . $shipment->id }}</td>
                            <td>{{ $shipment->originBranch->name ?? trans_db('admin.common.na', [], null, 'N/A') }}</td>
                            <td>{{ $shipment->destBranch->name ?? $shipment->receiver_city ?? trans_db('admin.common.na', [], null, 'N/A') }}</td>
                            <td>
                                @php
                                    $statusClasses = [
                                        'pending' => 'badge-secondary',
                                        'processing' => 'badge-primary',
                                        'in_transit' => 'bg-purple-500/20 text-purple-400 border border-purple-500/30',
                                        'out_for_delivery' => 'badge-warn',
                                        'delivered' => 'badge-success',
                                        'returned' => 'badge-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $statusClasses[$shipment->status] ?? 'badge-secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                </span>
                            </td>
                            <td class="muted text-sm">{{ $shipment->created_at->locale(app()->getLocale())->translatedFormat('M d, H:i') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-xs btn-secondary">{{ trans_db('admin.common.view', [], null, 'View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 muted">{{ trans_db('admin.dashboard.recent_shipments.empty', [], null, 'No shipments found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@php
    $chartLabelCreated = trans_db('admin.dashboard.charts.created', [], null, 'Created');
    $chartLabelDelivered = trans_db('admin.dashboard.charts.delivered', [], null, 'Delivered');
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update clock
    function updateTime() {
        const now = new Date();
        const locale = @json(app()->getLocale());
        document.getElementById('current-time').textContent = 
            now.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit', hour12: false });
    }
    setInterval(updateTime, 1000);

    // Shipments Chart
    const shipmentsCtx = document.getElementById('shipmentsChart')?.getContext('2d');
    if (shipmentsCtx) {
        new Chart(shipmentsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!},
                datasets: [{
                    label: @json($chartLabelCreated),
                    data: {!! json_encode($chartData['created'] ?? [12, 19, 15, 22, 18, 25, 20]) !!},
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    tension: 0.4,
                    fill: true,
                }, {
                    label: @json($chartLabelDelivered),
                    data: {!! json_encode($chartData['delivered'] ?? [10, 15, 12, 18, 15, 22, 18]) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }

    // Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($stats['status_breakdown'] ?? [])) !!},
                datasets: [{
                    data: {!! json_encode(array_values($stats['status_breakdown'] ?? [])) !!},
                    backgroundColor: ['#64748b', '#0ea5e9', '#a855f7', '#f59e0b', '#10b981', '#f43f5e'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endpush
@endsection
