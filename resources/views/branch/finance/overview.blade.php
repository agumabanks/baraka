<!-- Key Metrics -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-gray-400 mb-1">Total Outstanding</p>
                        <h3 class="text-white mb-0">{{ $defaultCurrency }} {{ number_format($totalOutstanding ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-red-500/10 p-3 rounded">
                        <i class="fas fa-exclamation-circle fa-2x text-red-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-gray-400 mb-1">Collected (MTD)</p>
                        <h3 class="text-white mb-0">{{ $defaultCurrency }} {{ number_format($totalCollected ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-green-500/10 p-3 rounded">
                        <i class="fas fa-check-circle fa-2x text-green-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-gray-400 mb-1">Revenue (MTD)</p>
                        <h3 class="text-white mb-0">{{ $defaultCurrency }} {{ number_format($totalRevenue ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-blue-500/10 p-3 rounded">
                        <i class="fas fa-chart-line fa-2x text-blue-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-gray-400 mb-1">Overdue Invoices</p>
                        <h3 class="text-white mb-0">{{ $overdueCount ?? 0 }}</h3>
                    </div>
                    <div class="bg-yellow-500/10 p-3 rounded">
                        <i class="fas fa-clock fa-2x text-yellow-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Aging Buckets -->
    <div class="col-md-6">
        <div class="card bg-gray-800 border-gray-700 h-100">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Receivables Aging</h5>
            </div>
            <div class="card-body">
                <canvas id="agingChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Collections Trend -->
    <div class="col-md-6">
        <div class="card bg-gray-800 border-gray-700 h-100">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Collections Trend (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="collectionsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Debtors & Revenue by Customer -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Top 10 Debtors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="text-end">Outstanding</th>
                                <th class="text-end">Invoices</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDebtors ?? [] as $debtor)
                            <tr>
                                <td>{{ $debtor->name }}</td>
                                <td class="text-end">{{ $defaultCurrency }} {{ number_format($debtor->total_outstanding, 2) }}</td>
                                <td class="text-end">{{ $debtor->invoice_count }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400">No outstanding debts</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Top 10 Revenue Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Invoices</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueByCustomer ?? [] as $customer)
                            <tr>
                                <td>{{ $customer->name }}</td>
                                <td class="text-end">{{ $defaultCurrency }} {{ number_format($customer->total_revenue, 2) }}</td>
                                <td class="text-end">{{ $customer->invoice_count }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400">No revenue data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Aging Chart
const agingCtx = document.getElementById('agingChart');
if (agingCtx) {
    new Chart(agingCtx, {
        type: 'bar',
        data: {
            labels: ['Current', '1-15 Days', '16-30 Days', '31+ Days'],
            datasets: [{
                label: 'Amount',
                data: [
                    {{ $aging['current'] ?? 0 }},
                    {{ $aging['bucket_1_15'] ?? 0 }},
                    {{ $aging['bucket_16_30'] ?? 0 }},
                    {{ $aging['bucket_31_plus'] ?? 0 }}
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#f97316', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#9ca3af' },
                    grid: { color: '#374151' }
                },
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: '#374151' }
                }
            }
        }
    });
}

// Collections Chart
const collectionsCtx = document.getElementById('collectionsChart');
if (collectionsCtx) {
    const dailyData = @json($dailyCollections ?? []);
    new Chart(collectionsCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Collections',
                data: dailyData.map(d => d.collected),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#9ca3af' },
                    grid: { color: '#374151' }
                },
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: '#374151' }
                }
            }
        }
    });
}
</script>
@endpush
