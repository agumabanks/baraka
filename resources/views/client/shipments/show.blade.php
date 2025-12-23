@extends('client.layout')

@section('title', trans_db('client.shipments.show.title', ['tracking' => ($shipment->tracking_number ?? $shipment->awb_number)], null, 'Shipment :tracking'))
@section('header', trans_db('client.shipments.show.header', [], null, 'Shipment Details'))

	@section('content')
	    <div class="mb-6">
	        <a href="{{ route('client.shipments.index') }}" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white transition-colors">
	            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
	            {{ trans_db('client.shipments.show.back', [], null, 'Back to Shipments') }}
	        </a>
	    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Tracking Info --}}
	            <div class="glass-panel p-6">
	                <div class="flex items-center justify-between mb-6">
	                    <div>
	                        <div class="text-sm text-zinc-400 mb-1">{{ trans_db('client.tracking.result.tracking_number', [], null, 'Tracking Number') }}</div>
	                        <div class="text-2xl font-mono font-bold">{{ $shipment->tracking_number ?? $shipment->awb_number }}</div>
	                    </div>
                    @php
                        $statusColors = [
                            'pending' => 'bg-zinc-500/20 text-zinc-400 border-zinc-500/30',
                            'booked' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                            'picked_up' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                            'in_transit' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
                            'out_for_delivery' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
                            'delivered' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                        ];
                        $color = $statusColors[strtolower($shipment->status)] ?? 'bg-zinc-500/20 text-zinc-400 border-zinc-500/30';
                    @endphp
	                    @php
	                        $statusKey = strtolower($shipment->status ?? 'pending');
	                        $statusDefault = ucfirst(str_replace('_', ' ', $statusKey));
	                    @endphp
	                    <span class="px-4 py-2 rounded-full text-sm font-medium border {{ $color }}">
	                        {{ trans_db("client.shipments.status.{$statusKey}", [], null, $statusDefault) }}
	                    </span>
	                </div>

                {{-- Progress Steps --}}
                <div class="relative">
                    <div class="flex justify-between mb-2">
                        @php
                            $steps = ['booked', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
                            $currentIndex = array_search(strtolower($shipment->status ?? 'pending'), $steps);
                            if ($currentIndex === false) $currentIndex = -1;
                        @endphp
	                        @foreach($steps as $index => $step)
                            <div class="flex flex-col items-center flex-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2 {{ $index <= $currentIndex ? 'bg-emerald-500' : 'bg-zinc-700' }}">
                                    @if($index <= $currentIndex)
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <span class="text-xs text-zinc-400">{{ $index + 1 }}</span>
                                    @endif
                                </div>
	                                <span class="text-xs text-center {{ $index <= $currentIndex ? 'text-white' : 'text-zinc-500' }}">{{ trans_db("client.shipments.status.{$step}", [], null, ucfirst(str_replace('_', ' ', $step))) }}</span>
	                            </div>
	                        @endforeach
                    </div>
                    <div class="absolute top-4 left-4 right-4 h-0.5 bg-zinc-700 -z-10">
                        <div class="h-full bg-emerald-500 transition-all" style="width: {{ max(0, min(100, ($currentIndex + 1) / count($steps) * 100)) }}%"></div>
                    </div>
                </div>
            </div>

	            {{-- Route Info --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.shipments.show.route.title', [], null, 'Route Information') }}</h3>
	                <div class="flex items-center gap-4">
	                    <div class="flex-1 p-4 bg-zinc-800/50 rounded-lg">
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.origin', [], null, 'Origin') }}</div>
	                        <div class="font-medium">{{ $shipment->originBranch?->name ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                        <div class="text-sm text-zinc-400">{{ $shipment->originBranch?->city }}, {{ $shipment->originBranch?->country }}</div>
	                    </div>
                    <svg class="w-6 h-6 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
	                    <div class="flex-1 p-4 bg-zinc-800/50 rounded-lg">
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.destination', [], null, 'Destination') }}</div>
	                        <div class="font-medium">{{ $shipment->destinationBranch?->name ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                        <div class="text-sm text-zinc-400">{{ $shipment->destinationBranch?->city }}, {{ $shipment->destinationBranch?->country }}</div>
	                    </div>
	                </div>
	            </div>

	            {{-- Package Details --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.shipments.show.package.title', [], null, 'Package Details') }}</h3>
	                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.package.weight', [], null, 'Weight') }}</div>
	                        <div class="font-medium">{{ number_format($shipment->weight ?? 0, 2) }} {{ trans_db('client.common.kg', [], null, 'kg') }}</div>
	                    </div>
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.package.pieces', [], null, 'Pieces') }}</div>
	                        <div class="font-medium">{{ $shipment->pieces ?? 1 }}</div>
	                    </div>
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.package.service', [], null, 'Service') }}</div>
	                        <div class="font-medium capitalize">{{ $shipment->service_level ?? trans_db('client.common.standard', [], null, 'Standard') }}</div>
	                    </div>
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.package.declared_value', [], null, 'Declared Value') }}</div>
	                        <div class="font-medium">${{ number_format($shipment->declared_value ?? 0, 2) }}</div>
	                    </div>
	                </div>
	                @if($shipment->description)
	                    <div class="mt-4 pt-4 border-t border-white/10">
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.package.description', [], null, 'Description') }}</div>
	                        <div class="text-sm">{{ $shipment->description }}</div>
	                    </div>
	                @endif
	            </div>
        </div>

        {{-- Sidebar --}}
	        <div class="space-y-6">
	            {{-- Payment Info --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.shipments.show.payment.title', [], null, 'Payment') }}</h3>
	                <div class="space-y-3">
	                    <div class="flex justify-between">
	                        <span class="text-zinc-400">{{ trans_db('client.shipments.show.payment.total_amount', [], null, 'Total Amount') }}</span>
	                        <span class="text-xl font-bold">${{ number_format($shipment->total_amount ?? 0, 2) }}</span>
	                    </div>
	                    <div class="flex justify-between text-sm">
	                        <span class="text-zinc-400">{{ trans_db('client.shipments.show.payment.status', [], null, 'Payment Status') }}</span>
	                        @php
	                            $paymentStatusKey = strtolower($shipment->payment_status ?? 'unpaid');
	                            $paymentStatusDefault = ucfirst($paymentStatusKey);
	                        @endphp
	                        <span class="px-2 py-0.5 rounded text-xs {{ ($shipment->payment_status ?? 'unpaid') === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
	                            {{ trans_db("client.shipments.payment_status.{$paymentStatusKey}", [], null, $paymentStatusDefault) }}
	                        </span>
	                    </div>
	                </div>
	            </div>

	            {{-- Receiver Info --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.shipments.show.receiver.title', [], null, 'Receiver') }}</h3>
	                <div class="space-y-3 text-sm">
	                    @php $meta = $shipment->metadata ?? []; @endphp
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.receiver.name', [], null, 'Name') }}</div>
	                        <div>{{ $meta['receiver_name'] ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                    </div>
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.receiver.phone', [], null, 'Phone') }}</div>
	                        <div>{{ $meta['receiver_phone'] ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                    </div>
	                    <div>
	                        <div class="text-xs text-zinc-500 uppercase mb-1">{{ trans_db('client.shipments.show.receiver.address', [], null, 'Address') }}</div>
	                        <div>{{ $meta['delivery_address'] ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                    </div>
	                </div>
	            </div>

	            {{-- Timeline --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.shipments.show.timeline.title', [], null, 'Timeline') }}</h3>
	                <div class="space-y-4">
	                    <div class="flex gap-3">
	                        <div class="w-2 h-2 rounded-full bg-emerald-500 mt-2"></div>
	                        <div>
	                            <div class="font-medium">{{ trans_db('client.shipments.show.timeline.created', [], null, 'Created') }}</div>
	                            <div class="text-sm text-zinc-400">{{ $shipment->created_at->locale(app()->getLocale())->translatedFormat('M d, Y H:i') }}</div>
	                        </div>
	                    </div>
	                    @if($shipment->updated_at && $shipment->updated_at != $shipment->created_at)
	                        <div class="flex gap-3">
	                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
	                            <div>
	                                <div class="font-medium">{{ trans_db('client.shipments.show.timeline.updated', [], null, 'Last Updated') }}</div>
	                                <div class="text-sm text-zinc-400">{{ $shipment->updated_at->locale(app()->getLocale())->translatedFormat('M d, Y H:i') }}</div>
	                            </div>
	                        </div>
	                    @endif
	                </div>
	            </div>
	        </div>
	    </div>
@endsection
