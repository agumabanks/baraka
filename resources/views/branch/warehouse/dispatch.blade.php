@extends('branch.layout')

@section('title', 'Dispatch Staging')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Dispatch Staging</h2>
            <p class="text-sm text-zinc-400">Stage and dispatch shipments for delivery or linehaul</p>
        </div>
        <div class="flex gap-2">
            <span class="chip bg-emerald-500/20 text-emerald-400">Dispatched Today: {{ $dispatchedToday }}</span>
            <a href="{{ route('branch.warehouse.index') }}" class="chip">Back to Warehouse</a>
        </div>
    </div>

    <!-- By Destination -->
    <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-6">
        @foreach($byDestination as $dest)
            <div class="glass-panel p-4 text-center">
                <div class="text-2xl font-bold text-blue-400">{{ $dest->count }}</div>
                <div class="text-xs text-zinc-400 mt-1">{{ $dest->destBranch?->code ?? 'Local' }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Ready for Dispatch -->
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-lg font-semibold">Ready for Dispatch</div>
                <div class="text-sm text-zinc-400">{{ $readyForDispatch->total() }} shipments</div>
            </div>
            <form method="POST" action="{{ route('branch.warehouse.dispatch.process') }}" id="dispatchForm">
                @csrf
                <div class="table-card">
                    <table class="dhl-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                <th>Tracking #</th>
                                <th>Customer</th>
                                <th>Destination</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($readyForDispatch as $shipment)
                                <tr>
                                    <td><input type="checkbox" name="shipment_ids[]" value="{{ $shipment->id }}" class="shipment-check"></td>
                                    <td class="font-mono text-sm">{{ $shipment->tracking_number }}</td>
                                    <td class="text-sm">{{ $shipment->customer?->name ?? 'N/A' }}</td>
                                    <td><span class="chip text-2xs">{{ $shipment->destBranch?->code ?? 'Local' }}</span></td>
                                    <td class="text-xs text-zinc-400">
                                        @php $status = is_object($shipment->current_status) ? $shipment->current_status->value : $shipment->current_status; @endphp
                                        {{ str_replace('_', ' ', $status) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-8 muted">No shipments ready for dispatch</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $readyForDispatch->links() }}</div>
            </form>
        </div>

        <!-- Dispatch Controls -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Dispatch Selected</div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs muted mb-1">Dispatch Type *</label>
                    <select form="dispatchForm" name="dispatch_type" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        <option value="delivery">Out for Delivery</option>
                        <option value="linehaul">Linehaul/Hub Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Assign Courier</label>
                    <select form="dispatchForm" name="worker_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        <option value="">Select courier...</option>
                        @foreach($availableCouriers as $courier)
                            <option value="{{ $courier->id }}">{{ $courier->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Notes</label>
                    <textarea form="dispatchForm" name="notes" rows="2" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2"></textarea>
                </div>
                <button type="submit" form="dispatchForm" class="chip w-full justify-center bg-blue-600 hover:bg-blue-500">
                    Dispatch Selected
                </button>
            </div>

            <div class="mt-6 pt-4 border-t border-white/10">
                <div class="text-sm font-semibold mb-2">Quick Actions</div>
                <div class="space-y-2">
                    <a href="{{ route('branch.warehouse.picking') }}" class="chip w-full justify-center">Picking List</a>
                    <a href="{{ route('branch.warehouse.capacity') }}" class="chip w-full justify-center">Capacity Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleAll(source) {
    document.querySelectorAll('.shipment-check').forEach(cb => cb.checked = source.checked);
}
</script>
@endpush
@endsection
