@extends('admin.layout')

@section('title', 'Merchant Settlements')
@section('header', 'Merchant Settlements')

@php
    $statusLabels = [
        'draft' => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'processing' => 'Processing',
    ];

    $statusColors = [
        'draft' => 'slate',
        'pending_approval' => 'amber',
        'approved' => 'blue',
        'paid' => 'emerald',
        'processing' => 'purple',
        'rejected' => 'rose',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.finance.dashboard') }}" class="chip text-sm">&larr; Back to Finance</a>
            <div class="text-sm muted">Review COD settlements awaiting approval or payment.</div>
        </div>

        <div class="glass-panel p-5 space-y-4">
            <form method="GET" action="{{ route('admin.finance.settlements.index') }}" class="flex flex-wrap gap-3 items-center">
                <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="pending_approval" @selected(request('status') === 'pending_approval')>Pending Approval</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                </select>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        name="merchant_id"
                        value="{{ request('merchant_id') }}"
                        placeholder="Merchant ID"
                        class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm"
                    >
                    <button type="submit" class="chip text-xs">Filter</button>
                </div>
            </form>

            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Settlement #</th>
                            <th>Merchant</th>
                            <th>Period</th>
                            <th>COD Collected</th>
                            <th>Shipping Fees</th>
                            <th>Net Payable</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr>
                                <td class="font-medium">{{ $settlement->settlement_number }}</td>
                                <td class="text-sm">
                                    <div class="font-semibold">{{ $settlement->merchant->name ?? 'Unknown merchant' }}</div>
                                    <div class="muted text-2xs">
                                        ID: {{ $settlement->merchant_id }}
                                        @if($settlement->merchant && $settlement->merchant->email)
                                            &bull; {{ $settlement->merchant->email }}
                                        @endif
                                    </div>
                                </td>
                                <td class="text-sm muted">
                                    {{ optional($settlement->period_start)->format('M d, Y') }} -
                                    {{ optional($settlement->period_end)->format('M d, Y') }}
                                </td>
                                <td class="text-sm">
                                    {{ $settlement->currency }} {{ number_format($settlement->total_cod_collected, 2) }}
                                </td>
                                <td class="text-sm">
                                    {{ $settlement->currency }} {{ number_format($settlement->total_shipping_fees, 2) }}
                                </td>
                                <td class="font-semibold text-emerald-400">
                                    {{ $settlement->currency }} {{ number_format($settlement->net_payable, 2) }}
                                </td>
                                <td>
                                    @php
                                        $color = $statusColors[$settlement->status] ?? 'slate';
                                        $label = $statusLabels[$settlement->status] ?? ucfirst(str_replace('_', ' ', $settlement->status));
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded text-2xs bg-{{ $color }}-500/20 text-{{ $color }}-300 border border-{{ $color }}-500/30">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.finance.settlements.statement', $settlement) }}" target="_blank" class="chip text-2xs">Statement</a>

                                        @if($settlement->status === 'draft')
                                            <button type="button" class="chip text-2xs bg-amber-600 hover:bg-amber-700"
                                                data-action="submit"
                                                data-url="{{ route('admin.finance.settlements.submit', $settlement) }}">
                                                Submit
                                            </button>
                                        @endif

                                        @if($settlement->status === 'pending_approval')
                                            <button type="button" class="chip text-2xs bg-blue-600 hover:bg-blue-700"
                                                data-action="approve"
                                                data-url="{{ route('admin.finance.settlements.approve', $settlement) }}">
                                                Approve
                                            </button>
                                        @endif

                                        @if($settlement->status === 'approved')
                                            <button type="button" class="chip text-2xs bg-emerald-600 hover:bg-emerald-700"
                                                data-action="pay"
                                                data-url="{{ route('admin.finance.settlements.pay', $settlement) }}">
                                                Mark Paid
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 muted">No settlements found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $settlements->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function postJson(url, payload = null) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: payload ? JSON.stringify(payload) : null,
            });

            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                // ignore JSON parse errors and fall through to generic message
            }

            if (!response.ok || data.success === false) {
                const message = data.message || 'Unable to complete the request.';
                throw new Error(message);
            }

            return data;
        }

        document.querySelectorAll('[data-action="submit"]').forEach(button => {
            button.addEventListener('click', async () => {
                if (!confirm('Submit this settlement for approval?')) {
                    return;
                }

                try {
                    await postJson(button.dataset.url);
                    window.location.reload();
                } catch (error) {
                    alert(error.message);
                }
            });
        });

        document.querySelectorAll('[data-action="approve"]').forEach(button => {
            button.addEventListener('click', async () => {
                if (!confirm('Approve this settlement?')) {
                    return;
                }

                try {
                    await postJson(button.dataset.url);
                    window.location.reload();
                } catch (error) {
                    alert(error.message);
                }
            });
        });

        document.querySelectorAll('[data-action="pay"]').forEach(button => {
            button.addEventListener('click', async () => {
                const payment_method = prompt('Payment method (e.g. bank_transfer, mobile_money, cash)');
                if (!payment_method) {
                    return;
                }

                const payment_reference = prompt('Payment reference / transaction ID');
                if (!payment_reference) {
                    return;
                }

                try {
                    await postJson(button.dataset.url, { payment_method, payment_reference });
                    window.location.reload();
                } catch (error) {
                    alert(error.message);
                }
            });
        });
    });
</script>
@endpush
