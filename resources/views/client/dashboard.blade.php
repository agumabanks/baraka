@extends('client.layout')

@section('title', trans_db('client.dashboard.title', [], null, 'Dashboard'))
@section('header', trans_db('client.dashboard.header', [], null, 'Dashboard'))

@section('content')
    {{-- Welcome Banner --}}
	    <div class="glass-panel p-6 mb-6">
	        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
	            <div>
	                <h2 class="text-2xl font-bold">{{ trans_db('client.dashboard.welcome', ['name' => $customer->contact_person], null, 'Welcome back, :name!') }}</h2>
	                <p class="text-zinc-400 mt-1">{{ trans_db('client.dashboard.subtitle', [], null, "Here's what's happening with your shipments today.") }}</p>
	            </div>
	            <div class="flex gap-3">
	                <a href="{{ route('client.shipments.create') }}" class="btn-primary">
	                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
	                    {{ trans_db('client.dashboard.actions.new_shipment', [], null, 'New Shipment') }}
	                </a>
	                <a href="{{ route('client.quotes') }}" class="btn-secondary">
	                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
	                    {{ trans_db('client.dashboard.actions.get_quote', [], null, 'Get Quote') }}
	                </a>
	            </div>
	        </div>
	    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
	            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
	                <span class="text-xs text-zinc-500">{{ trans_db('client.dashboard.stats.all_time', [], null, 'All Time') }}</span>
	            </div>
	            <div class="text-2xl font-bold">{{ number_format($stats['total_shipments']) }}</div>
	            <div class="text-sm text-zinc-400">{{ trans_db('client.dashboard.stats.total_shipments', [], null, 'Total Shipments') }}</div>
	        </div>
        
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
	                <span class="text-xs text-amber-400">{{ trans_db('client.dashboard.stats.active', [], null, 'Active') }}</span>
	            </div>
	            <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['pending_shipments']) }}</div>
	            <div class="text-sm text-zinc-400">{{ trans_db('client.dashboard.stats.in_transit', [], null, 'In Transit') }}</div>
	        </div>
        
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
	                <span class="text-xs text-emerald-400">{{ trans_db('client.dashboard.stats.complete', [], null, 'Complete') }}</span>
	            </div>
	            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['delivered_shipments']) }}</div>
	            <div class="text-sm text-zinc-400">{{ trans_db('client.dashboard.stats.delivered', [], null, 'Delivered') }}</div>
	        </div>
        
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
	                <span class="text-xs text-zinc-500">{{ trans_db('client.dashboard.stats.lifetime', [], null, 'Lifetime') }}</span>
	            </div>
	            <div class="text-2xl font-bold">${{ number_format($stats['total_spent'], 0) }}</div>
	            <div class="text-sm text-zinc-400">{{ trans_db('client.dashboard.stats.total_spent', [], null, 'Total Spent') }}</div>
	        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Recent Shipments --}}
        <div class="lg:col-span-2 glass-panel">
            <div class="p-5 border-b border-white/10 flex items-center justify-between">
	                <h3 class="font-semibold">{{ trans_db('client.dashboard.recent_shipments.title', [], null, 'Recent Shipments') }}</h3>
	                <a href="{{ route('client.shipments.index') }}" class="text-sm text-red-400 hover:text-red-300">{{ trans_db('client.common.view_all', [], null, 'View All') }}</a>
            </div>
            <div class="p-5">
                @if($shipments->count() > 0)
                    <div class="space-y-3">
                        @foreach($shipments as $shipment)
                            <a href="{{ route('client.shipments.show', $shipment) }}" class="block p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-mono text-sm font-medium">{{ $shipment->tracking_number ?? $shipment->awb_number }}</span>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-zinc-500/20 text-zinc-400',
                                            'booked' => 'bg-blue-500/20 text-blue-400',
                                            'picked_up' => 'bg-amber-500/20 text-amber-400',
                                            'in_transit' => 'bg-purple-500/20 text-purple-400',
                                            'out_for_delivery' => 'bg-orange-500/20 text-orange-400',
                                            'delivered' => 'bg-emerald-500/20 text-emerald-400',
                                        ];
                                        $color = $statusColors[strtolower($shipment->status)] ?? 'bg-zinc-500/20 text-zinc-400';
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-xs {{ $color }}">
                                        {{ ucfirst(str_replace('_', ' ', $shipment->status ?? 'pending')) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-zinc-400">
	                                    <span>{{ $shipment->created_at->locale(app()->getLocale())->translatedFormat('M d, Y') }}</span>
                                    <span class="font-medium text-white">${{ number_format($shipment->total_amount ?? 0, 2) }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
	                    <div class="text-center py-8">
	                        <svg class="w-12 h-12 text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
	                        <p class="text-zinc-400 mb-3">{{ trans_db('client.dashboard.recent_shipments.empty', [], null, 'No shipments yet') }}</p>
	                        <a href="{{ route('client.shipments.create') }}" class="btn-primary inline-flex">{{ trans_db('client.dashboard.recent_shipments.create_first', [], null, 'Create Your First Shipment') }}</a>
	                    </div>
	                @endif
            </div>
        </div>

        {{-- Quick Actions & Account Info --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="glass-panel p-5">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.dashboard.quick_actions.title', [], null, 'Quick Actions') }}</h3>
                <div class="space-y-2">
                    <a href="{{ route('client.tracking') }}" class="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
	                        <span>{{ trans_db('client.dashboard.quick_actions.track', [], null, 'Track a Shipment') }}</span>
                    </a>
                    <a href="{{ route('client.quotes') }}" class="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
	                        <span>{{ trans_db('client.dashboard.quick_actions.quote', [], null, 'Get a Quote') }}</span>
                    </a>
                    <a href="{{ route('client.addresses') }}" class="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        </div>
	                        <span>{{ trans_db('client.dashboard.quick_actions.addresses', [], null, 'Manage Addresses') }}</span>
                    </a>
                    <a href="{{ route('client.invoices') }}" class="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
	                        <span>{{ trans_db('client.dashboard.quick_actions.invoices', [], null, 'View Invoices') }}</span>
                    </a>
                </div>
            </div>

            {{-- Account Summary --}}
            <div class="glass-panel p-5">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.dashboard.account_summary.title', [], null, 'Account Summary') }}</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
	                        <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.customer_id', [], null, 'Customer ID') }}</span>
                        <span class="font-mono">{{ $customer->customer_code }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
	                        <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.account_type', [], null, 'Account Type') }}</span>
	                        <span class="capitalize">{{ $customer->customer_type ?? trans_db('client.dashboard.account_summary.account_type_regular', [], null, 'Regular') }}</span>
                    </div>
                    @if($customer->discount_rate > 0)
                        <div class="flex justify-between text-sm">
	                            <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.discount_rate', [], null, 'Discount Rate') }}</span>
                            <span class="text-emerald-400">{{ $customer->discount_rate }}%</span>
                        </div>
                    @endif
                    @if($customer->credit_limit > 0)
                        <div class="pt-3 border-t border-white/10">
                            <div class="flex justify-between text-sm mb-2">
	                                <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.credit_used', [], null, 'Credit Used') }}</span>
                                <span>${{ number_format($customer->current_balance ?? 0, 0) }} / ${{ number_format($customer->credit_limit, 0) }}</span>
                            </div>
                            @php $utilization = $customer->credit_limit > 0 ? (($customer->current_balance / $customer->credit_limit) * 100) : 0; @endphp
                            <div class="w-full h-2 bg-zinc-700 rounded-full">
                                <div class="h-full rounded-full {{ $utilization > 90 ? 'bg-rose-500' : ($utilization > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                    style="width: {{ min($utilization, 100) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
