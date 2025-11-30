@extends('admin.layout')

@section('title', 'Shipment #' . ($shipment->tracking_number ?? $shipment->id))
@section('header', 'Shipment Details')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- Header Actions --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.shipments.index') }}" class="text-sm text-sky-400 hover:text-sky-300 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Back to Shipments
            </a>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.shipments.edit', $shipment) }}" class="btn btn-sm btn-secondary">Edit</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Shipment Overview --}}
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-semibold">Shipment Overview</h2>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        @if($shipment->status === 'delivered') bg-emerald-500/20 text-emerald-400
                        @elseif($shipment->status === 'in_transit') bg-sky-500/20 text-sky-400
                        @elseif($shipment->status === 'cancelled') bg-red-500/20 text-red-400
                        @else bg-amber-500/20 text-amber-400
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $shipment->status ?? 'pending')) }}
                    </span>
                </div>
                <div class="p-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs muted uppercase mb-1">Tracking Number</div>
                        <div class="font-mono font-semibold text-sky-400">{{ $shipment->tracking_number ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Waybill Number</div>
                        <div class="font-mono">{{ $shipment->waybill_number ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Service Type</div>
                        <div>{{ ucfirst($shipment->service_type ?? $shipment->service_level ?? 'Standard') }}</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Priority</div>
                        <div>{{ ucfirst($shipment->priority ?? 'Normal') }}</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Created</div>
                        <div>{{ $shipment->created_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Delivered</div>
                        <div>{{ $shipment->delivered_at?->format('M d, Y H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Route Info --}}
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10">
                    <h2 class="font-semibold">Route Information</h2>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <div class="text-xs muted uppercase mb-1">Origin</div>
                            <div class="font-semibold">{{ $shipment->originBranch->name ?? 'N/A' }}</div>
                            <div class="text-sm muted">{{ $shipment->originBranch->address ?? '' }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-right">
                            <div class="text-xs muted uppercase mb-1">Destination</div>
                            <div class="font-semibold">{{ $shipment->destBranch->name ?? 'N/A' }}</div>
                            <div class="text-sm muted">{{ $shipment->destBranch->address ?? '' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Package Details --}}
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10">
                    <h2 class="font-semibold">Package Details</h2>
                </div>
                <div class="p-4 grid gap-4 md:grid-cols-4">
                    <div>
                        <div class="text-xs muted uppercase mb-1">Weight</div>
                        <div class="font-semibold">{{ $shipment->weight ?? 0 }} kg</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Declared Value</div>
                        <div class="font-semibold">{{ number_format($shipment->value ?? 0) }} UGX</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">COD Amount</div>
                        <div class="font-semibold">{{ number_format($shipment->cod_amount ?? 0) }} UGX</div>
                    </div>
                    <div>
                        <div class="text-xs muted uppercase mb-1">Shipping Cost</div>
                        <div class="font-semibold">{{ number_format($shipment->price_amount ?? 0) }} UGX</div>
                    </div>
                </div>
                @if($shipment->description)
                    <div class="px-4 pb-4">
                        <div class="text-xs muted uppercase mb-1">Description</div>
                        <div class="text-sm">{{ $shipment->description }}</div>
                    </div>
                @endif
            </div>

            {{-- Scan Events --}}
            @if($shipment->scanEvents && $shipment->scanEvents->count() > 0)
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10">
                    <h2 class="font-semibold">Tracking History</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($shipment->scanEvents->take(10) as $event)
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-sky-400"></div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="font-semibold text-sm">{{ $event->event_type ?? $event->scan_type ?? 'Update' }}</div>
                                            <div class="text-sm muted">{{ $event->location ?? $event->notes ?? '' }}</div>
                                        </div>
                                        <div class="text-xs muted">{{ $event->created_at?->format('M d, H:i') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10">
                    <h2 class="font-semibold">Customer</h2>
                </div>
                <div class="p-4">
                    <div class="text-sm font-semibold">{{ $shipment->customer->name ?? 'N/A' }}</div>
                    <div class="text-sm muted">{{ $shipment->customer->email ?? '' }}</div>
                    <div class="text-sm muted">{{ $shipment->customer->phone ?? '' }}</div>
                </div>
            </div>

            {{-- Assigned Driver --}}
            @if($shipment->assignedDriver)
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10">
                    <h2 class="font-semibold">Assigned Driver</h2>
                </div>
                <div class="p-4">
                    <div class="text-sm font-semibold">{{ $shipment->assignedDriver->name ?? 'N/A' }}</div>
                    <div class="text-sm muted">{{ $shipment->assignedDriver->phone ?? '' }}</div>
                </div>
            </div>
            @endif

            {{-- Timestamps --}}
            <div class="glass-panel">
                <div class="p-4 border-b border-white/10">
                    <h2 class="font-semibold">Timeline</h2>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="muted">Created</span>
                        <span>{{ $shipment->created_at?->format('M d, Y H:i') ?? '-' }}</span>
                    </div>
                    @if($shipment->picked_up_at)
                    <div class="flex justify-between">
                        <span class="muted">Picked Up</span>
                        <span>{{ $shipment->picked_up_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                    @if($shipment->out_for_delivery_at)
                    <div class="flex justify-between">
                        <span class="muted">Out for Delivery</span>
                        <span>{{ $shipment->out_for_delivery_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                    @if($shipment->delivered_at)
                    <div class="flex justify-between">
                        <span class="muted">Delivered</span>
                        <span class="text-emerald-400">{{ $shipment->delivered_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
