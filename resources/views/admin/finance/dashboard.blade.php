@extends('admin.layout')

@section('title', 'Finance Dashboard')
@section('header', 'Finance Dashboard')

@section('content')
<div id="finance-dashboard">
    {{-- Date Range Filter --}}
    <div class="glass-panel p-4 mb-6">
        <form id="date-filter" class="flex flex-wrap items-end gap-3">
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm preset-btn" data-preset="today">Today</button>
                <button type="button" class="btn btn-sm btn-primary preset-btn" data-preset="last_7_days">7 Days</button>
                <button type="button" class="btn btn-sm preset-btn" data-preset="last_30_days">30 Days</button>
                <button type="button" class="btn btn-sm preset-btn" data-preset="this_month">This Month</button>
            </div>
            <div class="flex-1"></div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="refreshDashboard()">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Refresh
            </button>
        </form>
    </div>

    {{-- Quick Stats --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">COD Pending</div>
                    <div class="text-2xl font-bold" id="stat-cod-pending">--</div>
                    <div class="text-emerald-400 text-xs" id="stat-cod-pending-count">-- collections</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">COD Collected</div>
                    <div class="text-2xl font-bold" id="stat-cod-collected">--</div>
                    <div class="text-emerald-400 text-xs" id="stat-collection-rate">--% collected</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">Pending Settlements</div>
                    <div class="text-2xl font-bold" id="stat-settlements-pending">--</div>
                    <div class="text-sky-400 text-xs" id="stat-settlements-count">-- settlements</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">Total Settled</div>
                    <div class="text-2xl font-bold" id="stat-total-settled">--</div>
                    <div class="text-purple-400 text-xs" id="stat-settled-count">-- paid</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid gap-6 lg:grid-cols-2 mb-6">
        {{-- COD Status Breakdown --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">COD Collection Status</h3>
            <div class="space-y-4" id="cod-status-breakdown">
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <span>Pending Collection</span>
                    </div>
                    <span class="font-semibold" id="cod-pending-count">--</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-sky-500"></div>
                        <span>Collected (Unverified)</span>
                    </div>
                    <span class="font-semibold" id="cod-collected-count">--</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span>Remitted</span>
                    </div>
                    <span class="font-semibold" id="cod-remitted-count">--</span>
                </div>
            </div>
        </div>

        {{-- Settlement Status --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">Settlement Pipeline</h3>
            <div class="space-y-4" id="settlement-pipeline">
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-slate-500"></div>
                        <span>Draft</span>
                    </div>
                    <span class="font-semibold" id="settlement-draft">--</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <span>Pending Approval</span>
                    </div>
                    <span class="font-semibold" id="settlement-pending">--</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-sky-500"></div>
                        <span>Approved</span>
                    </div>
                    <span class="font-semibold" id="settlement-approved">--</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span>Paid</span>
                    </div>
                    <span class="font-semibold" id="settlement-paid">--</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Driver Cash Accounts --}}
    <div class="glass-panel p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Driver Cash Balances</h3>
            <a href="{{ route('admin.finance.cod.driver-accounts') }}" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left py-3 px-4">Driver</th>
                        <th class="text-right py-3 px-4">Balance</th>
                        <th class="text-right py-3 px-4">Pending Remittance</th>
                        <th class="text-right py-3 px-4">Last Remittance</th>
                        <th class="text-center py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="driver-accounts-table">
                    <tr><td colspan="5" class="text-center py-8 muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="glass-panel p-5">
        <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('admin.finance.cod.needs-verification') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <div class="font-medium">Verify COD</div>
                    <div class="text-xs muted">Pending verification</div>
                </div>
            </a>
            <a href="{{ route('admin.finance.settlements.pending') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition">
                <div class="w-10 h-10 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <div>
                    <div class="font-medium">Approve Settlements</div>
                    <div class="text-xs muted">Pending approval</div>
                </div>
            </a>
            <a href="{{ route('admin.finance.cod.discrepancies') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition">
                <div class="w-10 h-10 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <div class="font-medium">Discrepancies</div>
                    <div class="text-xs muted">Review issues</div>
                </div>
            </a>
            <a href="{{ route('admin.finance.exchange-rates') }}" class="flex items-center gap-3 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </div>
                <div>
                    <div class="font-medium">Exchange Rates</div>
                    <div class="text-xs muted">Currency management</div>
                </div>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPreset = 'last_7_days';

document.addEventListener('DOMContentLoaded', function() {
    // Preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('btn-primary'));
            this.classList.add('btn-primary');
            currentPreset = this.dataset.preset;
            refreshDashboard();
        });
    });

    refreshDashboard();
});

function refreshDashboard() {
    fetch(`{{ route('admin.finance.dashboard.data') }}?preset=${currentPreset}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateDashboard(data.data);
            }
        })
        .catch(err => console.error('Error:', err));

    // Load driver accounts
    fetch(`{{ route('admin.finance.cod.driver-accounts') }}`, {
        headers: { 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateDriverTable(data.data);
            }
        });
}

function updateDashboard(data) {
    const cod = data.cod;
    const settlements = data.settlements;

    // COD stats
    document.getElementById('stat-cod-pending').textContent = formatCurrency(cod.amounts.pending_remittance);
    document.getElementById('stat-cod-pending-count').textContent = `${cod.counts.pending} pending`;
    document.getElementById('stat-cod-collected').textContent = formatCurrency(cod.amounts.total_collected);
    document.getElementById('stat-collection-rate').textContent = `${cod.collection_rate}% collected`;

    // COD breakdown
    document.getElementById('cod-pending-count').textContent = cod.counts.pending;
    document.getElementById('cod-collected-count').textContent = cod.counts.collected;
    document.getElementById('cod-remitted-count').textContent = cod.counts.remitted;

    // Settlement stats
    document.getElementById('stat-settlements-pending').textContent = formatCurrency(settlements.total_amount_pending);
    document.getElementById('stat-settlements-count').textContent = `${settlements.pending_approval + settlements.approved} pending`;
    document.getElementById('stat-total-settled').textContent = formatCurrency(settlements.total_amount_settled);
    document.getElementById('stat-settled-count').textContent = `${settlements.paid} paid`;

    // Settlement pipeline
    document.getElementById('settlement-draft').textContent = settlements.draft;
    document.getElementById('settlement-pending').textContent = settlements.pending_approval;
    document.getElementById('settlement-approved').textContent = settlements.approved;
    document.getElementById('settlement-paid').textContent = settlements.paid;
}

function updateDriverTable(drivers) {
    const tbody = document.getElementById('driver-accounts-table');
    
    if (!drivers || drivers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 muted">No driver accounts with balance</td></tr>';
        return;
    }

    tbody.innerHTML = drivers.slice(0, 10).map(d => `
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-3 px-4">${d.driver?.name || 'Unknown'}</td>
            <td class="py-3 px-4 text-right font-medium">${formatCurrency(d.balance)}</td>
            <td class="py-3 px-4 text-right text-amber-400">${formatCurrency(d.pending_remittance)}</td>
            <td class="py-3 px-4 text-right muted">${d.last_remittance_at ? new Date(d.last_remittance_at).toLocaleDateString() : 'Never'}</td>
            <td class="py-3 px-4 text-center">
                <a href="{{ url('admin/finance/cod/driver') }}/${d.driver_id}" class="btn btn-xs btn-secondary">View</a>
            </td>
        </tr>
    `).join('');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
}
</script>
@endpush
@endsection
