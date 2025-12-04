@forelse($shipments as $shipment)
    <tr class="hover:bg-white/5 transition" data-shipment-id="{{ $shipment->id }}">
        <td class="px-4 py-3">
            <input type="checkbox" class="shipment-checkbox shipment-select" value="{{ $shipment->id }}" onchange="updateBulkToolbar()">
        </td>
        <td class="px-4 py-3">
            <a href="#" onclick="showShipmentModal({{ $shipment->id }}); return false;" class="font-mono text-sm font-semibold text-sky-400 hover:text-sky-300">
                {{ $shipment->tracking_number ?? 'TRK-' . str_pad($shipment->id, 6, '0', STR_PAD_LEFT) }}
            </a>
            @if($shipment->waybill_number)
                <div class="text-xs text-zinc-500 mt-0.5">{{ $shipment->waybill_number }}</div>
            @endif
        </td>
        <td class="px-4 py-3">
            <div class="text-sm font-semibold">{{ $shipment->customer->contact_person ?? $shipment->customer->company_name ?? 'Walk-in' }}</div>
            <div class="text-xs text-zinc-500">{{ $shipment->customer->phone ?? $shipment->customer->email ?? '' }}</div>
        </td>
        <td class="px-4 py-3 text-sm">
            <div class="flex items-center gap-1 text-xs">
                <span class="text-zinc-400">{{ $shipment->originBranch->code ?? $shipment->originBranch->name ?? 'N/A' }}</span>
                <svg class="w-3 h-3 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <span class="text-zinc-400">{{ $shipment->destBranch->code ?? $shipment->destBranch->name ?? 'N/A' }}</span>
            </div>
        </td>
        <td class="px-4 py-3">
            @php
                $serviceBadges = [
                    'economy' => 'bg-zinc-500/20 text-zinc-400',
                    'standard' => 'bg-blue-500/20 text-blue-400',
                    'express' => 'bg-amber-500/20 text-amber-400',
                    'priority' => 'bg-red-500/20 text-red-400',
                ];
                $serviceClass = $serviceBadges[$shipment->service_level ?? 'standard'] ?? 'bg-zinc-500/20 text-zinc-400';
            @endphp
            <span class="px-2 py-1 text-xs rounded-full {{ $serviceClass }}">
                {{ ucfirst($shipment->service_level ?? 'Standard') }}
            </span>
        </td>
        <td class="px-4 py-3">
            @php
                $status = strtolower(str_replace(' ', '_', $shipment->status ?? $shipment->current_status ?? 'booked'));
            @endphp
            <span class="status-badge status-{{ $status }}">
                @if($status === 'delivered')
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                @elseif(in_array($status, ['in_transit', 'linehaul_departed']))
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                @endif
                {{ ucfirst(str_replace('_', ' ', $status)) }}
            </span>
        </td>
        <td class="px-4 py-3 text-sm text-zinc-400">
            <div>{{ $shipment->created_at->format('M d, Y') }}</div>
            <div class="text-xs text-zinc-500">{{ $shipment->created_at->format('h:i A') }}</div>
        </td>
        <td class="px-4 py-3 text-right">
            <div class="action-dropdown">
                <button class="px-3 py-1.5 bg-white/5 hover:bg-white/10 rounded-lg text-sm text-zinc-400 hover:text-white transition flex items-center gap-1">
                    Actions
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="action-dropdown-content">
                    <a href="#" onclick="showShipmentModal({{ $shipment->id }}); return false;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View Details
                    </a>
                    <a href="{{ route('admin.shipments.edit', $shipment) }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Shipment
                    </a>
                    <div class="divider"></div>
                    <a href="{{ route('admin.pos.label', $shipment) }}" target="_blank">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Label
                    </a>
                    <a href="{{ route('admin.pos.receipt', $shipment) }}" target="_blank">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Print Receipt
                    </a>
                    <div class="divider"></div>
                    <a href="#" onclick="showTrackingModal({{ $shipment->id }}, '{{ $shipment->tracking_number }}'); return false;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Track Shipment
                    </a>
                    <a href="#" onclick="showStatusModal({{ $shipment->id }}); return false;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Update Status
                    </a>
                    @if(!in_array($status, ['delivered', 'cancelled']))
                    <div class="divider"></div>
                    <button onclick="cancelShipment({{ $shipment->id }})" class="danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Cancel Shipment
                    </button>
                    @endif
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="px-4 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <div class="text-zinc-400 mb-2">No shipments found</div>
            <a href="{{ route('admin.pos.index') }}" class="text-sky-400 hover:text-sky-300 text-sm">Create your first shipment</a>
        </td>
    </tr>
@endforelse
