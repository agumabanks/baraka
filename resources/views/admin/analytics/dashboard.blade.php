@extends('admin.layout')

@section('title', 'Analytics Dashboard')
@section('header', 'Executive Analytics Dashboard')

@section('content')
<div id="analytics-dashboard">
    {{-- Date Range Filter --}}
    <div class="glass-panel p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <select id="date-preset" class="bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                    @foreach($presets ?? ['today' => 'Today', 'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days', 'this_month' => 'This Month', 'last_month' => 'Last Month'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['preset'] ?? 'last_30_days') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <div id="custom-date-range" class="hidden flex gap-2">
                    <input type="date" id="start-date" class="bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                    <input type="date" id="end-date" class="bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-primary" onclick="refreshDashboard()">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
                <a href="{{ route('admin.analytics.reports') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Shipments
                            </div>
                            <div class="h5 mb-0 font-weight-bold" id="kpi-total-shipments">-</div>
                            <div class="small text-muted" id="kpi-growth">
                                <span class="growth-indicator"></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Delivery Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold" id="kpi-delivery-rate">-</div>
                            <div class="small text-muted" id="kpi-delivered">- delivered</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold" id="kpi-revenue">-</div>
                            <div class="small text-muted" id="kpi-revenue-growth">
                                <span class="growth-indicator"></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                On-Time Delivery
                            </div>
                            <div class="h5 mb-0 font-weight-bold" id="kpi-ontime">-</div>
                            <div class="small text-muted">Target: 95%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Shipment Trends</h6>
                </div>
                <div class="card-body">
                    <canvas id="shipmentTrendsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Status Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row mb-4">
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">Avg Delivery Time</div>
                            <div class="h4" id="metric-avg-delivery">-</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">First Attempt Success</div>
                            <div class="h4" id="metric-first-attempt">-</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">SLA Compliance</div>
                            <div class="h4" id="metric-sla">-</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">Exception Rate</div>
                            <div class="h4" id="metric-exception">-</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">COD Collection</div>
                            <div class="h4" id="metric-cod">-</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">Active Shipments</div>
                            <div class="h4" id="metric-active">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Performance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Branch Performance</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="branch-performance-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Branch</th>
                                    <th>Shipments</th>
                                    <th>Delivered</th>
                                    <th>Delivery Rate</th>
                                    <th>Revenue</th>
                                    <th>Avg Delivery (hrs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }

#loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.growth-indicator.positive { color: #1cc88a; }
.growth-indicator.negative { color: #e74a3b; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let shipmentTrendsChart, statusChart, revenueChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    refreshDashboard();
    
    document.getElementById('date-preset').addEventListener('change', function() {
        if (this.value === 'custom') {
            document.getElementById('custom-date-range').classList.remove('d-none');
        } else {
            document.getElementById('custom-date-range').classList.add('d-none');
            refreshDashboard();
        }
    });
});

function initCharts() {
    // Shipment Trends Chart
    const trendsCtx = document.getElementById('shipmentTrendsChart').getContext('2d');
    shipmentTrendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Total Shipments',
                data: [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Delivered',
                data: [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Delivered', 'In Transit', 'Pending', 'Cancelled'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Revenue Trend Chart
    const revenueCtx = document.getElementById('revenueTrendChart').getContext('2d');
    revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue',
                data: [],
                backgroundColor: '#4e73df'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function refreshDashboard() {
    const preset = document.getElementById('date-preset').value;
    const params = new URLSearchParams({ preset });
    
    document.getElementById('loading-overlay').classList.remove('d-none');

    fetch(`/admin/analytics/data?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboard(data.data);
            }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
            document.getElementById('loading-overlay').classList.add('d-none');
        });

    // Load branch comparison
    fetch(`/admin/analytics/branch-comparison?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBranchTable(data.data);
            }
        });
}

function updateDashboard(data) {
    // Update KPIs
    document.getElementById('kpi-total-shipments').textContent = 
        data.overview.total_shipments.toLocaleString();
    
    const growthEl = document.querySelector('#kpi-growth .growth-indicator');
    const growth = data.overview.growth_rate;
    growthEl.className = 'growth-indicator ' + (growth >= 0 ? 'positive' : 'negative');
    growthEl.textContent = `${growth >= 0 ? '+' : ''}${growth}% vs previous period`;
    
    document.getElementById('kpi-delivery-rate').textContent = 
        `${data.overview.delivery_rate}%`;
    document.getElementById('kpi-delivered').textContent = 
        `${data.overview.delivered_shipments.toLocaleString()} delivered`;
    
    document.getElementById('kpi-revenue').textContent = 
        `$${data.financial.total_revenue.toLocaleString()}`;
    
    const revenueGrowthEl = document.querySelector('#kpi-revenue-growth .growth-indicator');
    const revenueGrowth = data.financial.revenue_growth;
    revenueGrowthEl.className = 'growth-indicator ' + (revenueGrowth >= 0 ? 'positive' : 'negative');
    revenueGrowthEl.textContent = `${revenueGrowth >= 0 ? '+' : ''}${revenueGrowth}% vs previous period`;
    
    document.getElementById('kpi-ontime').textContent = 
        `${data.performance.on_time_delivery_rate}%`;

    // Update metrics
    document.getElementById('metric-avg-delivery').textContent = 
        `${data.shipments.avg_delivery_hours} hrs`;
    document.getElementById('metric-first-attempt').textContent = 
        `${data.performance.first_attempt_success_rate}%`;
    document.getElementById('metric-sla').textContent = 
        `${data.performance.sla_compliance_rate}%`;
    document.getElementById('metric-exception').textContent = 
        `${data.performance.exception_rate}%`;
    document.getElementById('metric-cod').textContent = 
        `${data.financial.cod_collection_rate}%`;
    document.getElementById('metric-active').textContent = 
        data.overview.active_shipments.toLocaleString();

    // Update charts
    updateTrendsChart(data.trends);
    updateStatusChart(data.shipments.by_status);
    updateRevenueChart(data.trends);
}

function updateTrendsChart(trends) {
    shipmentTrendsChart.data.labels = trends.shipments.map(s => s.period);
    shipmentTrendsChart.data.datasets[0].data = trends.shipments.map(s => s.total);
    shipmentTrendsChart.data.datasets[1].data = trends.shipments.map(s => s.delivered);
    shipmentTrendsChart.update();
}

function updateStatusChart(statusData) {
    const delivered = statusData.delivered || 0;
    const inTransit = (statusData.in_transit || 0) + (statusData.out_for_delivery || 0);
    const pending = (statusData.pending || 0) + (statusData.booked || 0);
    const cancelled = (statusData.cancelled || 0) + (statusData.returned || 0);
    
    statusChart.data.datasets[0].data = [delivered, inTransit, pending, cancelled];
    statusChart.update();
}

function updateRevenueChart(trends) {
    revenueChart.data.labels = trends.revenue.map(r => r.period);
    revenueChart.data.datasets[0].data = trends.revenue.map(r => r.amount);
    revenueChart.update();
}

function updateBranchTable(branches) {
    const tbody = document.querySelector('#branch-performance-table tbody');
    
    if (!branches.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = branches.map(branch => `
        <tr>
            <td>${branch.name}</td>
            <td>${branch.total_shipments.toLocaleString()}</td>
            <td>${branch.delivered.toLocaleString()}</td>
            <td>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${branch.delivery_rate >= 90 ? 'bg-success' : branch.delivery_rate >= 70 ? 'bg-warning' : 'bg-danger'}" 
                         style="width: ${branch.delivery_rate}%">
                        ${branch.delivery_rate}%
                    </div>
                </div>
            </td>
            <td>$${branch.revenue.toLocaleString()}</td>
            <td>${branch.avg_delivery_hours} hrs</td>
        </tr>
    `).join('');
}
</script>
@endpush
