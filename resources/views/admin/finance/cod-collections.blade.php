@extends('admin.layout')

@section('title', 'COD Collections')
@section('header', 'COD Collections')

@php
    $statusLabels = [
        'pending' => 'Pending',
        'collected' => 'Collected',
        'verified' => 'Verified',
        'remitted' => 'Remitted',
    ];

    $statusColors = [
        'pending' => 'amber',
        'collected' => 'blue',
        'verified' => 'emerald',
        'remitted' => 'slate',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.finance.dashboard') }}" class="chip text-sm">&larr; Back to Finance</a>
            <div class="text-sm muted">Track COD collections, verification, and remittance state.</div>
        </div>

        <div class="glass-panel p-5 space-y-4">
            <form method="GET" action="{{ route('admin.finance.cod.index') }}" class="flex flex-wrap gap-3 items-center">
                <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="collected" @selected(request('status') === 'collected')>Collected</option>
                    <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                    <option value="remitted" @selected(request('status') === 'remitted')>Remitted</option>
                </select>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        name="branch_id"
                        value="{{ request('branch_id') }}"
                        placeholder="Branch ID"
                        class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm"
                    >
                    <button type="submit" class="chip text-xs">Filter</button>
                </div>
            </form>

            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Branch</th>
                            <th>Collector</th>
                            <th>Expected</th>
                            <th>Collected</th>
                            <th>Status</th>
                            <th>Dates</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collections as $collection)
                            <tr>
                                <td class="font-medium">
                                    {{ $collection->shipment->tracking_number ?? $collection->shipment_id }}
                                    @if($collection->shipment && $collection->shipment->customer)
                                        <div class="text-2xs muted">{{ $collection->shipment->customer->name }}</div>
                                    @endif
                                </td>
                                <td class="text-sm">
                                    {{ $collection->branch->name ?? 'N/A' }}
                                    <div class="text-2xs muted">ID: {{ $collection->branch_id ?? '—' }}</div>
                                </td>
                                <td class="text-sm">
                                    @if($collection->collector)
                                        <div class="font-semibold">{{ $collection->collector->name }}</div>
                                        <div class="text-2xs muted">{{ $collection->collector->email }}</div>
                                    @else
                                        <span class="muted text-2xs">Unassigned</span>
                                    @endif
                                </td>
                                <td class="text-sm">
                                    {{ $collection->currency ?? 'USD' }} {{ number_format($collection->expected_amount, 2) }}
                                </td>
                                <td class="text-sm {{ $collection->hasDiscrepancy() ? 'text-amber-400' : '' }}">
                                    @if($collection->collected_amount)
                                        {{ $collection->currency ?? 'USD' }} {{ number_format($collection->collected_amount, 2) }}
                                        @if($collection->hasDiscrepancy())
                                            <div class="text-2xs text-rose-300">Δ {{ number_format($collection->discrepancy, 2) }}</div>
                                        @endif
                                    @else
                                        <span class="muted text-2xs">Not recorded</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $color = $statusColors[$collection->status] ?? 'slate';
                                        $label = $statusLabels[$collection->status] ?? ucfirst($collection->status);
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded text-2xs bg-{{ $color }}-500/20 text-{{ $color }}-300 border border-{{ $color }}-500/30">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="text-2xs muted">
                                    <div>Created: {{ optional($collection->created_at)->format('M d, Y H:i') }}</div>
                                    @if($collection->collected_at)
                                        <div>Collected: {{ $collection->collected_at->format('M d, Y H:i') }}</div>
                                    @endif
                                    @if($collection->verified_at)
                                        <div>Verified: {{ $collection->verified_at->format('M d, Y H:i') }}</div>
                                    @endif
                                    @if($collection->remitted_at)
                                        <div>Remitted: {{ $collection->remitted_at->format('M d, Y H:i') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        @if($collection->status === 'pending')
                                            <button type="button" class="chip text-2xs bg-emerald-600 hover:bg-emerald-700"
                                                data-action="record"
                                                data-id="{{ $collection->id }}"
                                                data-url="{{ route('admin.finance.cod.record') }}">
                                                Record
                                            </button>
                                        @endif
                                        @if($collection->status === 'collected')
                                            <button type="button" class="chip text-2xs bg-blue-600 hover:bg-blue-700"
                                                data-action="verify"
                                                data-url="{{ route('admin.finance.cod.verify', $collection) }}">
                                                Verify
                                            </button>
                                        @endif
                                        @if($collection->payment_reference)
                                            <span class="chip text-2xs bg-slate-700 border border-white/10">
                                                Ref: {{ $collection->payment_reference }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 muted">No COD collections found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $collections->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            let data = {};
            try {
                data = await response.json();
            } catch (_) {}

            if (!response.ok || data.success === false) {
                const message = data.message || 'Unable to complete the request.';
                throw new Error(message);
            }
        }

        document.querySelectorAll('[data-action="record"]').forEach(button => {
            button.addEventListener('click', async () => {
                const amount = prompt('Collected amount:');
                if (!amount) return;
                const method = prompt('Collection method (e.g. cash, mobile_money, card):', 'cash');
                if (!method) return;
                const reference = prompt('Reference (optional):') || null;

                try {
                    await postJson(button.dataset.url, {
                        collection_id: button.dataset.id,
                        amount: parseFloat(amount),
                        method,
                        reference,
                    });
                    window.location.reload();
                } catch (error) {
                    alert(error.message);
                }
            });
        });

        document.querySelectorAll('[data-action="verify"]').forEach(button => {
            button.addEventListener('click', async () => {
                if (!confirm('Verify this collection?')) return;

                try {
                    await postJson(button.dataset.url, {});
                    window.location.reload();
                } catch (error) {
                    alert(error.message);
                }
            });
        });
    });
</script>
@endpush
