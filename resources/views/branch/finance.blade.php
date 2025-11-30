@extends('branch.layout')

@section('title', 'Finance')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 lg:col-span-2 space-y-4">
            <div class="grid sm:grid-cols-2 gap-3">
                <div class="stat-card">
                    <div class="text-xs uppercase muted">Outstanding</div>
                    <div class="text-2xl font-bold">{{ number_format($outstanding, 2) }} {{ $defaultCurrency ?? '' }}</div>
                    <div class="muted text-2xs">Invoices pending/partial</div>
                </div>
                <div class="stat-card">
                    <div class="text-xs uppercase muted">Collections</div>
                    <div class="text-2xl font-bold">{{ number_format($collected, 2) }} {{ $defaultCurrency ?? '' }}</div>
                    <div class="muted text-2xs">Logged payments for this branch</div>
                </div>
                @if(!empty($aging))
                    <div class="stat-card">
                        <div class="text-xs uppercase muted">Aging 1-15</div>
                        <div class="text-lg font-semibold">{{ number_format($aging['bucket_1_15'] ?? 0, 2) }} {{ $defaultCurrency ?? '' }}</div>
                        <div class="muted text-2xs">Coming due</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-xs uppercase muted">Aging 16-30</div>
                        <div class="text-lg font-semibold">{{ number_format($aging['bucket_16_30'] ?? 0, 2) }} {{ $defaultCurrency ?? '' }}</div>
                        <div class="muted text-2xs">Overdue</div>
                    </div>
                @endif
            </div>

            <div class="table-card">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div class="text-sm font-semibold">Invoices</div>
                    <span class="pill-soft">{{ $invoices->total() }} total</span>
                    <a href="{{ route('branch.finance.export', ['type' => 'invoices']) }}" class="chip text-2xs">Export CSV</a>
                </div>
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Shipment</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="font-semibold text-sm">{{ $invoice->invoice_number }}</td>
                                <td class="muted text-xs">#{{ $invoice->shipment_id }} {{ $invoice->shipment?->tracking_number }}</td>
                                <td>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
                                <td><span class="chip text-2xs">{{ $invoice->status }}</span></td>
                                <td class="muted text-xs">{{ optional($invoice->due_date)->toFormattedDateString() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 muted">No invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3">
                    {{ $invoices->links() }}
                </div>
            </div>

            <div class="table-card">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div class="text-sm font-semibold">Payments</div>
                    <span class="pill-soft">{{ $payments->total() }} logged</span>
                    <a href="{{ route('branch.finance.export', ['type' => 'payments']) }}" class="chip text-2xs">Export CSV</a>
                </div>
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Shipment</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Paid at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="font-semibold text-sm">#{{ $payment->shipment_id }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td class="muted text-xs">{{ $payment->payment_method }}</td>
                                <td class="muted text-xs">{{ optional($payment->paid_at)->toDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 muted">No payments logged.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="glass-panel p-5 space-y-2">
                <div class="text-sm font-semibold">Create invoice</div>
                <form method="POST" action="{{ route('branch.finance.invoice.store') }}" class="space-y-2 text-sm">
                    @csrf
                    <input type="number" name="shipment_id" placeholder="Shipment ID" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="total_amount" placeholder="Total amount" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="currency" placeholder="Currency (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="status" placeholder="Status (e.g., PENDING)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <textarea name="notes" placeholder="Notes" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2"></textarea>
                    <button class="chip w-full justify-center" type="submit">Save invoice</button>
                </form>
            </div>
            <div class="glass-panel p-5 space-y-2">
                <div class="text-sm font-semibold">Log payment</div>
                <form method="POST" action="{{ route('branch.finance.payment.store') }}" class="space-y-2 text-sm">
                    @csrf
                    <input type="number" name="shipment_id" placeholder="Shipment ID" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="amount" placeholder="Amount" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="payment_method" placeholder="Payment method" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="transaction_reference" placeholder="Reference" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="datetime-local" name="paid_at" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <button class="chip w-full justify-center" type="submit">Record payment</button>
                </form>
            </div>
        </div>
    </div>
@endsection
