{{-- Shipment Details Modal Content --}}
<div class="space-y-6">
    {{-- Header with Status --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-2xl font-bold text-white font-mono">{{ $shipment->tracking_number ?? 'TRK-' . str_pad($shipment->id, 6, '0', STR_PAD_LEFT) }}</div>
            @if($shipment->waybill_number)
                <div class="text-sm text-zinc-400">Waybill: {{ $shipment->waybill_number }}</div>
            @endif
        </div>
        @php
            $status = strtolower(str_replace(' ', '_', $shipment->status ?? $shipment->current_status ?? 'booked'));
            $statusColors = [
                'booked' => 'bg-blue-500/20 text-blue-400',
                'picked_up' => 'bg-purple-500/20 text-purple-400',
                'in_transit' => 'bg-amber-500/20 text-amber-400',
                'at_origin_hub' => 'bg-teal-500/20 text-teal-400',
                'at_destination_hub' => 'bg-teal-500/20 text-teal-400',
                'out_for_delivery' => 'bg-orange-500/20 text-orange-400',
                'delivered' => 'bg-green-500/20 text-green-400',
                'cancelled' => 'bg-red-500/20 text-red-400',
                'returned' => 'bg-red-500/20 text-red-400',
            ];
        @endphp
        <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $statusColors[$status] ?? 'bg-zinc-500/20 text-zinc-400' }}">
            {{ ucfirst(str_replace('_', ' ', $status)) }}
        </span>
    </div>

    {{-- Route Info --}}
    <div class="bg-white/5 rounded-xl p-4">
        <div class="flex items-center gap-4">
            <div class="flex-1 text-center">
                <div class="text-xs text-zinc-500 uppercase mb-1">Origin</div>
                <div class="text-lg font-semibold text-white">{{ $shipment->originBranch->name ?? 'N/A' }}</div>
                <div class="text-xs text-zinc-400">{{ $shipment->originBranch->code ?? '' }}</div>
            </div>
            <div class="flex items-center gap-2 text-zinc-500">
                <div class="w-8 h-0.5 bg-zinc-600"></div>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <div class="w-8 h-0.5 bg-zinc-600"></div>
            </div>
            <div class="flex-1 text-center">
                <div class="text-xs text-zinc-500 uppercase mb-1">Destination</div>
                <div class="text-lg font-semibold text-white">{{ $shipment->destBranch->name ?? 'N/A' }}</div>
                <div class="text-xs text-zinc-400">{{ $shipment->destBranch->code ?? '' }}</div>
            </div>
        </div>
    </div>

    {{-- Customer & Receiver --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white/5 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase mb-2">Sender</div>
            <div class="text-white font-semibold">{{ $shipment->customer->name ?? $shipment->sender_name ?? 'N/A' }}</div>
            <div class="text-sm text-zinc-400">{{ $shipment->customer->email ?? $shipment->sender_phone ?? '' }}</div>
            <div class="text-sm text-zinc-500 mt-1">{{ $shipment->customer->mobile ?? '' }}</div>
        </div>
        <div class="bg-white/5 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase mb-2">Receiver</div>
            <div class="text-white font-semibold">{{ $shipment->receiver_name ?? 'N/A' }}</div>
            <div class="text-sm text-zinc-400">{{ $shipment->receiver_phone ?? '' }}</div>
            <div class="text-sm text-zinc-500 mt-1">{{ $shipment->receiver_address ?? '' }}</div>
        </div>
    </div>

    {{-- Shipment Details --}}
    <div class="bg-white/5 rounded-xl p-4">
        <div class="text-xs text-zinc-500 uppercase mb-3">Shipment Details</div>
        <div class="grid grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-zinc-500">Weight</div>
                <div class="text-white font-semibold">{{ number_format($shipment->weight ?? 0, 2) }} kg</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500">Service</div>
                <div class="text-white font-semibold">{{ ucfirst($shipment->service_level ?? $shipment->service_type ?? 'Standard') }}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500">Amount</div>
                <div class="text-white font-semibold">UGX {{ number_format($shipment->total_amount ?? $shipment->amount ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500">COD</div>
                <div class="text-white font-semibold">{{ $shipment->cod_amount ? 'UGX ' . number_format($shipment->cod_amount) : 'N/A' }}</div>
            </div>
        </div>
        @if($shipment->description)
        <div class="mt-3 pt-3 border-t border-white/10">
            <div class="text-xs text-zinc-500">Description</div>
            <div class="text-white">{{ $shipment->description }}</div>
        </div>
        @endif
    </div>

    {{-- Tracking Timeline --}}
    @if($shipment->scanEvents && $shipment->scanEvents->count() > 0)
    <div class="bg-white/5 rounded-xl p-4">
        <div class="text-xs text-zinc-500 uppercase mb-3">Tracking History</div>
        <div class="space-y-3 max-h-48 overflow-y-auto">
            @foreach($shipment->scanEvents->take(5) as $event)
            <div class="flex items-start gap-3">
                <div class="w-2 h-2 mt-2 rounded-full bg-sky-500"></div>
                <div class="flex-1">
                    <div class="text-white text-sm">{{ ucfirst(str_replace('_', ' ', $event->scan_type ?? $event->event_type ?? 'Update')) }}</div>
                    <div class="text-xs text-zinc-500">{{ $event->created_at->format('M d, Y h:i A') }} - {{ $event->location ?? $event->branch->name ?? '' }}</div>
                    @if($event->notes)
                        <div class="text-xs text-zinc-400 mt-1">{{ $event->notes }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @if($shipment->scanEvents->count() > 5)
        <div class="mt-2 text-center">
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-sky-400 hover:text-sky-300 text-sm">View all {{ $shipment->scanEvents->count() }} events →</a>
        </div>
        @endif
    </div>
    @endif

    {{-- Dates --}}
    <div class="grid grid-cols-3 gap-4 text-center">
        <div>
            <div class="text-xs text-zinc-500">Created</div>
            <div class="text-white">{{ $shipment->created_at->format('M d, Y') }}</div>
            <div class="text-xs text-zinc-500">{{ $shipment->created_at->format('h:i A') }}</div>
        </div>
        @if($shipment->picked_up_at)
        <div>
            <div class="text-xs text-zinc-500">Picked Up</div>
            <div class="text-white">{{ \Carbon\Carbon::parse($shipment->picked_up_at)->format('M d, Y') }}</div>
            <div class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($shipment->picked_up_at)->format('h:i A') }}</div>
        </div>
        @endif
        @if($shipment->delivered_at)
        <div>
            <div class="text-xs text-zinc-500">Delivered</div>
            <div class="text-white">{{ \Carbon\Carbon::parse($shipment->delivered_at)->format('M d, Y') }}</div>
            <div class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($shipment->delivered_at)->format('h:i A') }}</div>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 pt-4 border-t border-white/10">
        <a href="{{ route('admin.pos.label', $shipment) }}" target="_blank" class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg text-center transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Label
        </a>
        <a href="{{ route('admin.pos.receipt', $shipment) }}" target="_blank" class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg text-center transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Print Receipt
        </a>
        <a href="{{ route('admin.shipments.edit', $shipment) }}" class="flex-1 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-center transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>
</div>
