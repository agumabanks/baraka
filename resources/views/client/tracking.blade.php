@extends('client.layout')

@section('title', trans_db('client.tracking.title', [], null, 'Track Shipment'))
@section('header', trans_db('client.tracking.header', [], null, 'Track Shipment'))

@section('content')
	    <div class="max-w-3xl mx-auto">
	        {{-- Search Form --}}
	        <div class="glass-panel p-8 mb-6">
	            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full bg-red-500/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
	                <h2 class="text-xl font-bold mb-2">{{ trans_db('client.tracking.hero.title', [], null, 'Track Your Shipment') }}</h2>
	                <p class="text-zinc-400">{{ trans_db('client.tracking.hero.subtitle', [], null, 'Enter your tracking number or AWB to get the latest status') }}</p>
	            </div>
	            <form method="GET" action="{{ route('client.tracking') }}" class="flex gap-3">
	                <input type="text" name="awb" value="{{ $awb }}" placeholder="{{ trans_db('client.tracking.search.placeholder', [], null, 'Enter tracking number...') }}" 
	                    class="input-field flex-1 text-lg py-3" autofocus>
	                <button type="submit" class="btn-primary px-8">
	                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
	                    {{ trans_db('client.tracking.search.submit', [], null, 'Track') }}
	                </button>
	            </form>
	        </div>

        @if($awb)
	            @if($shipment)
	                {{-- Shipment Found --}}
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
                    <div class="relative mb-8">
                        <div class="flex justify-between mb-2">
                            @php
                                $steps = ['booked', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
                                $currentIndex = array_search(strtolower($shipment->status ?? 'pending'), $steps);
                                if ($currentIndex === false) $currentIndex = -1;
                            @endphp
	                            @foreach($steps as $index => $step)
	                                <div class="flex flex-col items-center flex-1">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 {{ $index <= $currentIndex ? 'bg-emerald-500' : 'bg-zinc-700' }}">
                                        @if($index <= $currentIndex)
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <span class="text-sm text-zinc-400">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
	                                    <span class="text-xs text-center {{ $index <= $currentIndex ? 'text-white' : 'text-zinc-500' }}">{{ trans_db("client.shipments.status.{$step}", [], null, ucfirst(str_replace('_', ' ', $step))) }}</span>
	                                </div>
	                            @endforeach
	                        </div>
                        <div class="absolute top-5 left-5 right-5 h-0.5 bg-zinc-700 -z-10">
                            <div class="h-full bg-emerald-500 transition-all" style="width: {{ max(0, min(100, ($currentIndex + 1) / count($steps) * 100)) }}%"></div>
                        </div>
                    </div>

                    {{-- Shipment Details --}}
	                    <div class="grid md:grid-cols-2 gap-6">
	                        <div class="p-4 bg-zinc-800/50 rounded-lg">
	                            <div class="text-xs text-zinc-500 uppercase mb-2">{{ trans_db('client.tracking.result.from', [], null, 'From') }}</div>
	                            <div class="font-medium">{{ $shipment->originBranch?->name ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                            <div class="text-sm text-zinc-400">{{ $shipment->originBranch?->city }}, {{ $shipment->originBranch?->country }}</div>
	                        </div>
	                        <div class="p-4 bg-zinc-800/50 rounded-lg">
	                            <div class="text-xs text-zinc-500 uppercase mb-2">{{ trans_db('client.tracking.result.to', [], null, 'To') }}</div>
	                            <div class="font-medium">{{ $shipment->destinationBranch?->name ?? trans_db('client.common.na', [], null, 'N/A') }}</div>
	                            <div class="text-sm text-zinc-400">{{ $shipment->destinationBranch?->city }}, {{ $shipment->destinationBranch?->country }}</div>
	                        </div>
	                    </div>

	                    <div class="mt-6 pt-6 border-t border-white/10 flex justify-between text-sm">
	                        <div>
	                            <span class="text-zinc-400">{{ trans_db('client.tracking.result.created', [], null, 'Created') }}:</span>
	                            <span>{{ $shipment->created_at->locale(app()->getLocale())->translatedFormat('M d, Y H:i') }}</span>
	                        </div>
	                        <div>
	                            <span class="text-zinc-400">{{ trans_db('client.tracking.result.weight', [], null, 'Weight') }}:</span>
	                            <span>{{ number_format($shipment->weight ?? 0, 2) }} {{ trans_db('client.common.kg', [], null, 'kg') }}</span>
	                        </div>
	                        <div>
	                            <span class="text-zinc-400">{{ trans_db('client.tracking.result.service', [], null, 'Service') }}:</span>
	                            <span class="capitalize">{{ $shipment->service_level ?? trans_db('client.common.standard', [], null, 'Standard') }}</span>
	                        </div>
	                    </div>
	                </div>
	            @else
                {{-- Not Found --}}
                <div class="glass-panel p-8 text-center">
                    <svg class="w-16 h-16 text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
	                    <h3 class="text-lg font-medium mb-2">{{ trans_db('client.tracking.not_found.title', [], null, 'Shipment Not Found') }}</h3>
	                    <p class="text-zinc-400 mb-4">{{ trans_db('client.tracking.not_found.message', ['tracking' => $awb], null, "We couldn't find a shipment with tracking number: :tracking") }}</p>
	                    <p class="text-sm text-zinc-500">{{ trans_db('client.tracking.not_found.hint', [], null, 'Please check the tracking number and try again.') }}</p>
	                </div>
	            @endif
	        @endif
	    </div>
@endsection
