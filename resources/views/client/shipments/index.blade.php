@extends('client.layout')

@section('title', trans_db('client.shipments.title', [], null, 'My Shipments'))
@section('header', trans_db('client.shipments.header', [], null, 'My Shipments'))

@section('content')
	    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
	        <div class="flex gap-4">
	            <div class="stat-card flex-1">
	                <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
	                <div class="text-sm text-zinc-400">{{ trans_db('client.shipments.stats.total', [], null, 'Total') }}</div>
	            </div>
	            <div class="stat-card flex-1">
	                <div class="text-2xl font-bold text-amber-400">{{ $stats['in_transit'] }}</div>
	                <div class="text-sm text-zinc-400">{{ trans_db('client.shipments.stats.in_transit', [], null, 'In Transit') }}</div>
	            </div>
	            <div class="stat-card flex-1">
	                <div class="text-2xl font-bold text-emerald-400">{{ $stats['delivered'] }}</div>
	                <div class="text-sm text-zinc-400">{{ trans_db('client.shipments.stats.delivered', [], null, 'Delivered') }}</div>
	            </div>
	        </div>
	        <a href="{{ route('client.shipments.create') }}" class="btn-primary">
	            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
	            {{ trans_db('client.shipments.actions.new', [], null, 'New Shipment') }}
	        </a>
	    </div>

    <div class="glass-panel">
	        <div class="p-5 border-b border-white/10">
	            <form method="GET" class="flex flex-wrap gap-3">
	                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans_db('client.shipments.search.placeholder', [], null, 'Search tracking number...') }}"
	                    class="input-field flex-1 min-w-[200px]">
	                <select name="status" class="input-field w-40" onchange="this.form.submit()">
	                    <option value="">{{ trans_db('client.shipments.search.all_statuses', [], null, 'All Statuses') }}</option>
	                    <option value="pending" @selected(request('status') === 'pending')>{{ trans_db('client.shipments.status.pending', [], null, 'Pending') }}</option>
	                    <option value="booked" @selected(request('status') === 'booked')>{{ trans_db('client.shipments.status.booked', [], null, 'Booked') }}</option>
	                    <option value="in_transit" @selected(request('status') === 'in_transit')>{{ trans_db('client.shipments.status.in_transit', [], null, 'In Transit') }}</option>
	                    <option value="delivered" @selected(request('status') === 'delivered')>{{ trans_db('client.shipments.status.delivered', [], null, 'Delivered') }}</option>
	                </select>
	                <button type="submit" class="btn-secondary">{{ trans_db('client.common.search', [], null, 'Search') }}</button>
	            </form>
	        </div>

        <div class="divide-y divide-white/10">
	            @forelse($shipments as $shipment)
	                <a href="{{ route('client.shipments.show', $shipment) }}" class="block p-5 hover:bg-white/5 transition-colors">
	                    <div class="flex items-center justify-between mb-2">
	                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
	                            <div>
	                                <div class="font-mono font-medium">{{ $shipment->tracking_number ?? $shipment->awb_number }}</div>
	                                <div class="text-sm text-zinc-400">{{ $shipment->created_at->locale(app()->getLocale())->translatedFormat('M d, Y H:i') }}</div>
	                            </div>
	                        </div>
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
	                        @php
	                            $statusKey = strtolower($shipment->status ?? 'pending');
	                            $statusDefault = ucfirst(str_replace('_', ' ', $statusKey));
	                        @endphp
	                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $color }}">
	                            {{ trans_db("client.shipments.status.{$statusKey}", [], null, $statusDefault) }}
	                        </span>
	                    </div>
	                    <div class="flex items-center justify-between text-sm">
	                        <div class="text-zinc-400">
	                            {{ $shipment->originBranch?->city ?? trans_db('client.shipments.origin', [], null, 'Origin') }} → {{ $shipment->destinationBranch?->city ?? trans_db('client.shipments.destination', [], null, 'Destination') }}
	                        </div>
	                        <div class="font-medium">${{ number_format($shipment->total_amount ?? 0, 2) }}</div>
	                    </div>
	                </a>
	            @empty
	                <div class="p-12 text-center">
	                    <svg class="w-12 h-12 text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
	                    <p class="text-zinc-400 mb-4">{{ trans_db('client.shipments.empty', [], null, 'No shipments found') }}</p>
	                    <a href="{{ route('client.shipments.create') }}" class="btn-primary inline-flex">{{ trans_db('client.shipments.create_first', [], null, 'Create Your First Shipment') }}</a>
	                </div>
	            @endforelse
	        </div>

        @if($shipments->hasPages())
            <div class="p-5 border-t border-white/10">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
@endsection
