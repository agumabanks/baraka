@extends('branch.layout')

@section('title', 'Warehouse Inventory')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Inventory Overview</h2>
            <p class="text-sm text-zinc-400">Track parcels and packages currently in warehouse</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('branch.warehouse.receiving') }}" class="chip">Receiving</a>
            <a href="{{ route('branch.warehouse.dispatch') }}" class="chip">Dispatch</a>
        </div>
    </div>

    <!-- Age Analysis -->
    <div class="grid gap-4 md:grid-cols-3">
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Fresh (0-24h)</div>
            <div class="text-2xl font-bold text-emerald-400">{{ $ageAnalysis['0-24h'] }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Aging (24-48h)</div>
            <div class="text-2xl font-bold text-amber-400">{{ $ageAnalysis['24-48h'] }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Critical (48h+)</div>
            <div class="text-2xl font-bold text-rose-400">{{ $ageAnalysis['48h+'] }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Inventory List -->
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-lg font-semibold">In Warehouse</div>
                <div class="text-sm text-zinc-400">{{ $inWarehouse->total() }} items</div>
            </div>
            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Status</th>
                            <th>Route</th>
                            <th>Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inWarehouse as $shipment)
                            <tr>
                                <td>
                                    <a href="{{ route('branch.shipments.show', $shipment) }}" class="font-mono text-sm text-emerald-400 hover:underline">
                                        {{ $shipment->tracking_number }}
                                    </a>
                                </td>
                                <td>
                                    @php
                                        $status = is_object($shipment->current_status) ? $shipment->current_status->value : $shipment->current_status;
                                    @endphp
                                    <span class="chip text-2xs">{{ str_replace('_', ' ', $status) }}</span>
                                </td>
                                <td class="text-sm">
                                    {{ $shipment->originBranch?->code ?? '?' }} → {{ $shipment->destBranch?->code ?? '?' }}
                                </td>
                                <td class="text-sm text-zinc-400">{{ $shipment->updated_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-8 muted">No items in warehouse</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $inWarehouse->links() }}</div>
        </div>

        <!-- Location Summary -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">By Location Type</div>
            <div class="space-y-3">
                @foreach($locationSummary as $type => $locations)
                    <div class="p-3 border border-white/5 rounded-lg">
                        <div class="font-semibold text-sm mb-2">{{ $type }}</div>
                        @foreach($locations as $loc)
                            <div class="flex items-center justify-between text-sm py-1">
                                <span class="text-zinc-400">{{ $loc->code }}</span>
                                <span class="chip text-2xs">{{ $loc->shipments_count ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('branch.warehouse.zones') }}" class="chip w-full justify-center">Manage Zones</a>
            </div>
        </div>
    </div>
</div>
@endsection
