@extends('branch.layout')

@section('title', 'Finance Dashboard')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Finance Dashboard</h1>
        <p class="text-sm muted">Financial reporting and analytics for {{ $branch->name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('branch.finance.daily-report') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Daily Report
        </a>
        <div class="relative dropdown-container">
            <button type="button" onclick="toggleExportDropdown()" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-48 bg-zinc-900 border border-white/10 rounded-xl shadow-2xl z-50 py-1">
                <a href="{{ route('branch.finance.export', ['type' => 'invoices']) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-white/5">Invoices CSV</a>
                <a href="{{ route('branch.finance.export', ['type' => 'payments']) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-white/5">Payments CSV</a>
            </div>
        </div>
    </div>
</div>

@include('branch.finance._nav')

@if($view === 'overview')
    {{-- Stats Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="glass-panel p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xs uppercase muted mb-1">Total Outstanding</div>
                    <div class="text-2xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($totalOutstanding ?? 0, 0) }}</div>
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
                    <div class="text-2xs uppercase muted mb-1">Collected (MTD)</div>
                    <div class="text-2xl font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format($totalCollected ?? 0, 0) }}</div>
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
                    <div class="text-2xs uppercase muted mb-1">Revenue (MTD)</div>
                    <div class="text-2xl font-bold text-blue-400">{{ $defaultCurrency }} {{ number_format($totalRevenue ?? 0, 0) }}</div>
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
                    <div class="text-2xs uppercase muted mb-1">Overdue Invoices</div>
                    <div class="text-2xl font-bold text-amber-400">{{ $overdueCount ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 mb-6">
        {{-- Aging Buckets --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Receivables Aging</div>
            <div class="space-y-3">
                @php
                    $agingData = [
                        ['label' => 'Current', 'value' => $aging['current'] ?? 0, 'color' => 'emerald'],
                        ['label' => '1-15 Days', 'value' => $aging['bucket_1_15'] ?? 0, 'color' => 'amber'],
                        ['label' => '16-30 Days', 'value' => $aging['bucket_16_30'] ?? 0, 'color' => 'orange'],
                        ['label' => '31+ Days', 'value' => $aging['bucket_31_plus'] ?? 0, 'color' => 'rose'],
                    ];
                    $maxAging = max(1, max(array_column($agingData, 'value')));
                @endphp
                @foreach($agingData as $bucket)
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm">{{ $bucket['label'] }}</span>
                            <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($bucket['value'], 0) }}</span>
                        </div>
                        <div class="w-full h-2 bg-zinc-700 rounded-full overflow-hidden">
                            <div class="h-full bg-{{ $bucket['color'] }}-500 rounded-full" style="width: {{ ($bucket['value'] / $maxAging) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Collections Chart --}}
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-lg font-semibold">Collections Trend</div>
                <select onchange="changeCollectionPeriod(this.value)" class="bg-zinc-800 border border-white/10 rounded px-2 py-1 text-sm">
                    <option value="week" {{ ($collectionPeriod ?? 'month') === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ ($collectionPeriod ?? 'month') === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="quarter" {{ ($collectionPeriod ?? 'month') === 'quarter' ? 'selected' : '' }}>This Quarter</option>
                </select>
            </div>
            <div class="h-48">
                <canvas id="collectionsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Top Debtors --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Top Debtors</div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-800/50 text-xs uppercase text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-right">Outstanding</th>
                            <th class="px-4 py-3 text-right">Invoices</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($topDebtors ?? [] as $debtor)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium">{{ $debtor->name }}</td>
                                <td class="px-4 py-3 text-right text-rose-400">{{ $defaultCurrency }} {{ number_format($debtor->total_outstanding, 0) }}</td>
                                <td class="px-4 py-3 text-right text-zinc-400">{{ $debtor->invoice_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-zinc-500">No outstanding debts</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Revenue Customers --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Top Revenue Customers</div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-800/50 text-xs uppercase text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                            <th class="px-4 py-3 text-right">Invoices</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($revenueByCustomer ?? [] as $customer)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-right text-emerald-400">{{ $defaultCurrency }} {{ number_format($customer->total_revenue, 0) }}</td>
                                <td class="px-4 py-3 text-right text-zinc-400">{{ $customer->invoice_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-zinc-500">No revenue data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@elseif($view === 'receivables')
    {{-- Receivables View --}}
    <div class="grid gap-4 md:grid-cols-3 mb-6">
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Total Outstanding</div>
            <div class="text-2xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($totalOutstanding ?? 0, 0) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Overdue Amount</div>
            <div class="text-2xl font-bold text-amber-400">{{ $defaultCurrency }} {{ number_format(($aging['bucket_16_30'] ?? 0) + ($aging['bucket_31_plus'] ?? 0), 0) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Overdue Invoices</div>
            <div class="text-2xl font-bold">{{ $overdueCount ?? 0 }}</div>
        </div>
    </div>

    <div class="glass-panel p-5">
        <div class="text-lg font-semibold mb-4">Outstanding by Customer</div>
        <div class="overflow-x-auto rounded-lg border border-white/10">
            <table class="w-full">
                <thead class="bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase text-zinc-400">
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Outstanding</th>
                        <th class="px-4 py-3 text-right">Invoices</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($topDebtors ?? [] as $debtor)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $debtor->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-400">{{ $defaultCurrency }} {{ number_format($debtor->total_outstanding, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ $debtor->invoice_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('branch.clients.show', $debtor->id) }}" class="text-emerald-400 hover:text-emerald-300 text-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">No outstanding receivables</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif($view === 'invoices')
    {{-- Invoices View --}}
    <div class="glass-panel p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
            <div class="text-lg font-semibold">All Invoices</div>
            <div class="flex gap-2">
                <select id="statusFilter" onchange="filterInvoices()" class="bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="PENDING" {{ ($statusFilter ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="SENT" {{ ($statusFilter ?? '') === 'SENT' ? 'selected' : '' }}>Sent</option>
                    <option value="PAID" {{ ($statusFilter ?? '') === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="OVERDUE" {{ ($statusFilter ?? '') === 'OVERDUE' ? 'selected' : '' }}>Overdue</option>
                    <option value="CANCELLED" {{ ($statusFilter ?? '') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-white/10">
            <table class="w-full">
                <thead class="bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase text-zinc-400">
                        <th class="px-4 py-3">Invoice #</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Due Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($invoices ?? [] as $invoice)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 font-mono text-sm">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3">{{ $invoice->customer?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $invoice->currency ?? $defaultCurrency }} {{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'PENDING' => 'bg-zinc-500/20 text-zinc-400',
                                        'SENT' => 'bg-blue-500/20 text-blue-400',
                                        'PAID' => 'bg-emerald-500/20 text-emerald-400',
                                        'OVERDUE' => 'bg-rose-500/20 text-rose-400',
                                        'CANCELLED' => 'bg-zinc-500/20 text-zinc-500',
                                    ];
                                    $statusValue = is_object($invoice->status) ? $invoice->status->value : $invoice->status;
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$statusValue] ?? 'bg-zinc-500/20 text-zinc-400' }}">
                                    {{ $statusValue }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No invoices found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($invoices) && $invoices->hasPages())
            <div class="mt-4">{{ $invoices->links() }}</div>
        @endif
    </div>
@endif

{{-- Quick Actions Sidebar Modal --}}
<div id="quickActionsModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeQuickActions()"></div>
    <div class="fixed right-0 top-0 h-full w-full max-w-md bg-zinc-900 border-l border-white/10 overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Quick Actions</h3>
                <button onclick="closeQuickActions()" class="p-2 hover:bg-white/10 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Create Invoice --}}
            <div class="glass-panel p-4 mb-4">
                <div class="text-sm font-semibold mb-3">Create Invoice</div>
                <form method="POST" action="{{ route('branch.finance.invoice.store') }}" class="space-y-3">
                    @csrf
                    <input type="number" name="shipment_id" placeholder="Shipment ID" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                    <input type="number" name="total_amount" step="0.01" placeholder="Total Amount" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                    <select name="status" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="PENDING">Pending</option>
                        <option value="SENT">Sent</option>
                    </select>
                    <textarea name="notes" placeholder="Notes (optional)" rows="2" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm"></textarea>
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium text-sm">Create Invoice</button>
                </form>
            </div>

            {{-- Log Payment --}}
            <div class="glass-panel p-4">
                <div class="text-sm font-semibold mb-3">Log Payment</div>
                <form method="POST" action="{{ route('branch.finance.payment.store') }}" class="space-y-3">
                    @csrf
                    <input type="number" name="shipment_id" placeholder="Shipment ID" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                    <input type="number" name="amount" step="0.01" placeholder="Amount" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                    <select name="payment_method" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                    </select>
                    <input type="text" name="transaction_reference" placeholder="Reference (optional)" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium text-sm">Record Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Floating Action Button --}}
<button onclick="openQuickActions()" class="fixed bottom-6 right-6 w-14 h-14 bg-emerald-600 hover:bg-emerald-700 rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110 z-40">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
</button>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function toggleExportDropdown() {
    document.getElementById('exportDropdown').classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-container')) {
        document.getElementById('exportDropdown')?.classList.add('hidden');
    }
});

function changeCollectionPeriod(period) {
    window.location.href = `{{ route('branch.finance.index') }}?view=overview&period=${period}`;
}

function filterInvoices() {
    const status = document.getElementById('statusFilter').value;
    window.location.href = `{{ route('branch.finance.index') }}?view=invoices${status ? '&status=' + status : ''}`;
}

function openQuickActions() {
    document.getElementById('quickActionsModal').classList.remove('hidden');
}

function closeQuickActions() {
    document.getElementById('quickActionsModal').classList.add('hidden');
}

// Collections Chart
@if($view === 'overview')
const collectionsCtx = document.getElementById('collectionsChart');
if (collectionsCtx) {
    const dailyData = @json($dailyCollections ?? []);
    new Chart(collectionsCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Collections',
                data: dailyData.map(d => parseFloat(d.collected) || 0),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } }
            }
        }
    });
}
@endif
</script>
@endpush
