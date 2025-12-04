@extends('branch.layout')

@section('title', 'COD Management')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">COD Management</h1>
        <p class="text-sm muted">Cash on Delivery tracking and reconciliation</p>
    </div>
</div>

@include('branch.finance._nav')

{{-- Stats Cards --}}
<div class="grid gap-4 md:grid-cols-4 mb-6">
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Outstanding COD</div>
                <div class="text-2xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($outstandingCod ?? 0, 0) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Today's Collections</div>
                <div class="text-2xl font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format($todayCollections ?? 0, 0) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">MTD Collections</div>
                <div class="text-2xl font-bold text-blue-400">{{ $defaultCurrency }} {{ number_format($mtdCollections ?? 0, 0) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Pending Shipments</div>
                <div class="text-2xl font-bold text-amber-400">{{ $pendingCod->total() }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        {{-- Pending COD Shipments --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Pending COD Collections</div>
            <div class="overflow-x-auto rounded-lg border border-white/10">
                <table class="w-full">
                    <thead class="bg-zinc-800/50">
                        <tr class="text-left text-xs uppercase text-zinc-400">
                            <th class="px-4 py-3">Tracking #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Courier</th>
                            <th class="px-4 py-3 text-right">COD Amount</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($pendingCod as $shipment)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-mono text-sm">{{ $shipment->tracking_number }}</td>
                                <td class="px-4 py-3">{{ $shipment->customer?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-400">{{ $shipment->assignedWorker?->user?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-400">{{ $defaultCurrency }} {{ number_format($shipment->cod_amount, 0) }}</td>
                                <td class="px-4 py-3">
                                    <button onclick="openReconcileModal({{ $shipment->id }}, '{{ $shipment->tracking_number }}', {{ $shipment->cod_amount }})" 
                                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-xs font-medium">
                                        Reconcile
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No pending COD shipments</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pendingCod->hasPages())
                <div class="mt-4">{{ $pendingCod->links() }}</div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        {{-- COD by Worker --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Outstanding by Courier</div>
            <div class="space-y-3">
                @forelse($codByWorker ?? [] as $worker)
                    <div class="flex items-center justify-between p-3 bg-zinc-800/50 rounded-lg">
                        <div>
                            <div class="font-medium">{{ $worker->name }}</div>
                            <div class="text-xs text-zinc-400">{{ $worker->shipment_count }} shipments</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-rose-400">{{ $defaultCurrency }} {{ number_format($worker->pending_amount, 0) }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-zinc-500 text-sm">No outstanding COD by worker</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Reconcile Modal --}}
<div id="reconcileModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeReconcileModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="glass-panel w-full max-w-md">
            <form method="POST" action="{{ route('branch.finance.cod.reconcile') }}">
                @csrf
                <input type="hidden" name="shipment_id" id="reconcile_shipment_id">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Reconcile COD</h3>
                    <p class="text-sm text-zinc-400 mb-4">Shipment: <span id="reconcile_tracking" class="font-mono text-white"></span></p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Expected Amount</label>
                            <input type="text" id="reconcile_expected" readonly class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Amount Collected <span class="text-rose-400">*</span></label>
                            <input type="number" name="amount_collected" step="0.01" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Collection Method <span class="text-rose-400">*</span></label>
                            <select name="collection_method" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                    <button type="button" onclick="closeReconcileModal()" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-sm font-medium">Reconcile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReconcileModal(shipmentId, tracking, amount) {
    document.getElementById('reconcile_shipment_id').value = shipmentId;
    document.getElementById('reconcile_tracking').textContent = tracking;
    document.getElementById('reconcile_expected').value = '{{ $defaultCurrency }} ' + amount.toLocaleString();
    document.getElementById('reconcileModal').classList.remove('hidden');
}

function closeReconcileModal() {
    document.getElementById('reconcileModal').classList.add('hidden');
}
</script>
@endsection
