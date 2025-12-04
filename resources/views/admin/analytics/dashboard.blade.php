@extends('admin.layout')

@section('title', 'Analytics Dashboard')
@section('header', 'Executive Analytics Dashboard')

@section('content')
<div id="analytics-dashboard" class="space-y-6 max-w-7xl mx-auto">
    {{-- Filters --}}
    <div class="rounded-xl border border-white/5 bg-slate-900/80 shadow-lg backdrop-blur px-4 py-4 lg:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wide text-slate-400">Range</span>
                    <select id="date-preset" class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        @foreach($presets ?? ['today' => 'Today', 'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days', 'this_month' => 'This Month', 'last_month' => 'Last Month'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['preset'] ?? 'last_30_days') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="custom-date-range" class="hidden items-center gap-2">
                    <input type="date" id="start-date" class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    <input type="date" id="end-date" class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 justify-between lg:justify-end">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Updated <span id="last-updated">--</span></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-400 transition" onclick="refreshDashboard()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh
                    </button>
                    <a href="{{ route('admin.analytics.reports') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-slate-100 shadow hover:bg-white/20 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="w-12 h-12 border-4 border-white/30 border-t-indigo-500 rounded-full animate-spin"></div>
        <div class="mt-3 text-sm text-slate-200">Refreshing insights…</div>
    </div>

    <!-- KPI Cards -->
    <div class="grid gap-4 lg:grid-cols-4 md:grid-cols-2">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Total Shipments</div>
                    <div class="text-3xl font-semibold text-white mt-1" id="kpi-total-shipments">-</div>
                </div>
                <div id="kpi-growth" class="pill">
                    <span class="growth-indicator"></span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Delivery Rate</div>
                    <div class="text-3xl font-semibold text-white mt-1" id="kpi-delivery-rate">-</div>
                    <div class="text-sm text-slate-300" id="kpi-delivered">- delivered</div>
                </div>
                <div class="icon-chip bg-emerald-500/20 text-emerald-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Total Revenue</div>
                    <div class="text-3xl font-semibold text-white mt-1" id="kpi-revenue">-</div>
                    <div class="text-sm text-slate-300" id="kpi-revenue-growth">
                        <span class="growth-indicator"></span>
                    </div>
                </div>
                <div class="icon-chip bg-indigo-500/20 text-indigo-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"></path></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">On-Time Delivery</div>
                    <div class="text-3xl font-semibold text-white mt-1" id="kpi-ontime">-</div>
                    <div class="text-sm text-slate-300">Target: 95%</div>
                </div>
                <div class="icon-chip bg-amber-500/20 text-amber-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2 2m6-2a8 8 0 11-16 0 8 8 0 0116 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="dashboard-panel xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-white">Shipment Trends</div>
                    <div class="text-xs text-slate-400">Volume vs delivered across the selected window</div>
                </div>
            </div>
            <div class="chart-shell h-80">
                <canvas id="shipmentTrendsChart"></canvas>
                <div id="trends-empty" class="chart-empty hidden">No shipment activity in this range</div>
            </div>
        </div>

        <div class="dashboard-panel">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-white">Status Distribution</div>
                    <div class="text-xs text-slate-400">Delivered / in transit / pending / cancelled</div>
                </div>
            </div>
            <div class="chart-shell h-80">
                <canvas id="statusDistributionChart"></canvas>
                <div id="status-empty" class="chart-empty hidden">No status data</div>
            </div>
        </div>
    </div>

    <!-- Performance + Revenue -->
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="dashboard-panel xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-white">Revenue Trend</div>
                    <div class="text-xs text-slate-400">Daily revenue trajectory</div>
                </div>
            </div>
            <div class="chart-shell h-64">
                <canvas id="revenueTrendChart"></canvas>
                <div id="revenue-empty" class="chart-empty hidden">No revenue recorded in this range</div>
            </div>
        </div>

        <div class="dashboard-panel">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-white">Performance Metrics</div>
                    <div class="text-xs text-slate-400">Operational excellence snapshot</div>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="mini-metric">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400">Avg Delivery Time</div>
                    <div class="text-xl font-semibold text-white" id="metric-avg-delivery">-</div>
                </div>
                <div class="mini-metric">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400">First Attempt Success</div>
                    <div class="text-xl font-semibold text-white" id="metric-first-attempt">-</div>
                </div>
                <div class="mini-metric">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400">SLA Compliance</div>
                    <div class="text-xl font-semibold text-white" id="metric-sla">-</div>
                </div>
                <div class="mini-metric">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400">Exception Rate</div>
                    <div class="text-xl font-semibold text-white" id="metric-exception">-</div>
                </div>
                <div class="mini-metric">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400">COD Collection</div>
                    <div class="text-xl font-semibold text-white" id="metric-cod">-</div>
                </div>
                <div class="mini-metric">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400">Active Shipments</div>
                    <div class="text-xl font-semibold text-white" id="metric-active">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Performance Table -->
    <div class="dashboard-panel">
        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-sm font-semibold text-white">Branch Performance</div>
                <div class="text-xs text-slate-400">Top branches by volume, quality, and revenue</div>
            </div>
        </div>
        <div class="overflow-auto">
            <table class="min-w-full text-sm text-slate-200" id="branch-performance-table">
                <thead class="text-xs uppercase tracking-wide text-slate-400 border-b border-white/5">
                    <tr>
                        <th class="py-3 pr-4 text-left font-semibold table-cell-label">Branch</th>
                        <th class="py-3 px-4 text-left font-semibold table-cell-number">Shipments</th>
                        <th class="py-3 px-4 text-left font-semibold table-cell-number">Delivered</th>
                        <th class="py-3 px-4 text-left font-semibold table-cell-label">Delivery Rate</th>
                        <th class="py-3 px-4 text-left font-semibold table-cell-number">Revenue</th>
                        <th class="py-3 px-4 text-left font-semibold table-cell-number">Avg Delivery (hrs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="py-4 text-center text-slate-400">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
:root {
    color-scheme: dark;
}

#analytics-dashboard {
    color: #e5e7eb;
}

.dashboard-panel {
    background: radial-gradient(circle at 10% 20%, rgba(99,102,241,0.05), transparent 25%), linear-gradient(135deg, #0f172a 0%, #0b1224 100%);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}

.stat-card {
    background: linear-gradient(135deg, rgba(59,130,246,0.14), rgba(59,130,246,0.05));
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
}

.stat-card:nth-child(2) {
    background: linear-gradient(135deg, rgba(16,185,129,0.18), rgba(16,185,129,0.05));
}

.stat-card:nth-child(3) {
    background: linear-gradient(135deg, rgba(99,102,241,0.18), rgba(99,102,241,0.06));
}

.stat-card:nth-child(4) {
    background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.06));
}

.icon-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
}

.mini-metric {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 10px 12px;
}

.pill {
    padding: 6px 10px;
    border-radius: 9999px;
    background: rgba(255,255,255,0.08);
    color: #e5e7eb;
    font-size: 12px;
    font-weight: 600;
    min-width: 120px;
    text-align: center;
}

#loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(9,10,20,0.8);
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 12px;
    z-index: 9999;
    backdrop-filter: blur(4px);
}

#loading-overlay.is-visible {
    display: flex;
}

#branch-performance-table tbody tr:hover {
    background: rgba(255,255,255,0.03);
}

.chart-shell {
    position: relative;
}

.chart-empty {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 0.9rem;
    background: linear-gradient(135deg, rgba(15,23,42,0.85), rgba(12,16,32,0.85));
    border: 1px dashed rgba(255,255,255,0.08);
    border-radius: 12px;
    pointer-events: none;
}

.chart-empty.hidden { display: none; }

.table-cell-number {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.table-cell-label {
    text-align: left;
}

.table-row + .table-row {
    border-top: 1px solid rgba(255,255,255,0.04);
}

.growth-indicator { color: #e5e7eb; }
.growth-indicator.positive { color: #22c55e; }
.growth-indicator.negative { color: #f43f5e; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let shipmentTrendsChart, statusChart, revenueChart;

const chartTheme = {
    text: 'rgba(226,232,240,0.9)',
    grid: 'rgba(255,255,255,0.08)',
    border: 'rgba(148,163,184,0.35)',
    primary: '#5b8def',
    secondary: '#34d399',
    accent: '#fbbf24',
    danger: '#f43f5e',
    neutral: '#94a3b8',
    tooltipBg: '#0f172a'
};

const fmtNumber = (value = 0) => Number(value ?? 0).toLocaleString();
const fmtPercent = (value = 0, decimals = 1) => `${Number(value ?? 0).toFixed(decimals)}%`;
const fmtHours = (value = 0, decimals = 1) => `${Number(value ?? 0).toFixed(decimals)} hrs`;
const fmtCurrency = (value = 0) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value ?? 0));

document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.color = chartTheme.text;
    Chart.defaults.font.family = '"Inter var", "SF Pro Text", system-ui, -apple-system, sans-serif';
    Chart.defaults.plugins.legend.labels.color = chartTheme.text;

    initCharts();
    refreshDashboard();
    
    document.getElementById('date-preset').addEventListener('change', function() {
        if (this.value === 'custom') {
            document.getElementById('custom-date-range').classList.remove('hidden');
        } else {
            document.getElementById('custom-date-range').classList.add('hidden');
            refreshDashboard();
        }
    });
});

function initCharts() {
    // Shipment Trends Chart
    const trendsCtx = document.getElementById('shipmentTrendsChart').getContext('2d');
    const trendPrimary = trendsCtx.createLinearGradient(0, 0, 0, trendsCtx.canvas.height);
    trendPrimary.addColorStop(0, 'rgba(91, 141, 239, 0.35)');
    trendPrimary.addColorStop(1, 'rgba(91, 141, 239, 0.05)');

    const trendDelivered = trendsCtx.createLinearGradient(0, 0, 0, trendsCtx.canvas.height);
    trendDelivered.addColorStop(0, 'rgba(52, 211, 153, 0.32)');
    trendDelivered.addColorStop(1, 'rgba(52, 211, 153, 0.05)');

    shipmentTrendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Total Shipments',
                data: [],
                borderColor: chartTheme.primary,
                backgroundColor: trendPrimary,
                borderWidth: 2,
                pointRadius: 0,
                fill: true,
                tension: 0.35
            }, {
                label: 'Delivered',
                data: [],
                borderColor: chartTheme.secondary,
                backgroundColor: trendDelivered,
                borderWidth: 2,
                pointRadius: 0,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                tooltip: {
                    backgroundColor: chartTheme.tooltipBg,
                    borderColor: chartTheme.grid,
                    borderWidth: 1,
                    padding: 12
                }
            },
            scales: {
                x: { 
                    grid: { color: chartTheme.grid },
                    ticks: { color: chartTheme.text, font: { size: 11 } }
                },
                y: { 
                    beginAtZero: true,
                    grid: { color: chartTheme.grid },
                    ticks: { color: chartTheme.text, font: { size: 11 } }
                }
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
                backgroundColor: [chartTheme.secondary, '#38bdf8', chartTheme.accent, chartTheme.danger],
                borderColor: chartTheme.border,
                borderWidth: 1,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '62%',
            plugins: { 
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                tooltip: {
                    backgroundColor: chartTheme.tooltipBg,
                    borderColor: chartTheme.grid,
                    borderWidth: 1,
                    padding: 10
                }
            }
        }
    });

    // Revenue Trend Chart
    const revenueCtx = document.getElementById('revenueTrendChart').getContext('2d');
    const revenueGradient = revenueCtx.createLinearGradient(0, 0, 0, revenueCtx.canvas.height);
    revenueGradient.addColorStop(0, 'rgba(99, 102, 241, 0.55)');
    revenueGradient.addColorStop(1, 'rgba(99, 102, 241, 0.08)');

    revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue',
                data: [],
                backgroundColor: revenueGradient,
                borderColor: chartTheme.primary,
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: chartTheme.tooltipBg,
                    borderColor: chartTheme.grid,
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: { 
                    grid: { color: chartTheme.grid },
                    ticks: { color: chartTheme.text, font: { size: 11 } }
                },
                y: { 
                    beginAtZero: true,
                    grid: { color: chartTheme.grid },
                    ticks: { color: chartTheme.text, font: { size: 11 } }
                }
            }
        }
    });
}

function refreshDashboard() {
    const preset = document.getElementById('date-preset').value;
    const params = new URLSearchParams({ preset });
    
    document.getElementById('loading-overlay').classList.add('is-visible');

    fetch(`/admin/analytics/data?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboard(data.data);
            }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
            document.getElementById('loading-overlay').classList.remove('is-visible');
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
    document.getElementById('kpi-total-shipments').textContent = fmtNumber(data.overview.total_shipments);
    
    const growthEl = document.querySelector('#kpi-growth .growth-indicator');
    const growth = data.overview.growth_rate;
    growthEl.className = 'growth-indicator ' + (growth >= 0 ? 'positive' : 'negative');
    growthEl.textContent = `${growth >= 0 ? '+' : ''}${fmtPercent(growth, 1)} vs previous period`;
    
    document.getElementById('kpi-delivery-rate').textContent = fmtPercent(data.overview.delivery_rate, 1);
    document.getElementById('kpi-delivered').textContent = `${fmtNumber(data.overview.delivered_shipments)} delivered`;
    
    document.getElementById('kpi-revenue').textContent = fmtCurrency(data.financial.total_revenue);
    
    const revenueGrowthEl = document.querySelector('#kpi-revenue-growth .growth-indicator');
    const revenueGrowth = data.financial.revenue_growth;
    revenueGrowthEl.className = 'growth-indicator ' + (revenueGrowth >= 0 ? 'positive' : 'negative');
    revenueGrowthEl.textContent = `${revenueGrowth >= 0 ? '+' : ''}${fmtPercent(revenueGrowth, 1)} vs previous period`;
    
    document.getElementById('kpi-ontime').textContent = fmtPercent(data.performance.on_time_delivery_rate, 1);

    const generatedAt = data.generated_at ? new Date(data.generated_at) : null;
    document.getElementById('last-updated').textContent = generatedAt 
        ? generatedAt.toLocaleString() 
        : 'just now';

    // Update metrics
    document.getElementById('metric-avg-delivery').textContent = fmtHours(data.shipments.avg_delivery_hours);
    document.getElementById('metric-first-attempt').textContent = fmtPercent(data.performance.first_attempt_success_rate, 1);
    document.getElementById('metric-sla').textContent = fmtPercent(data.performance.sla_compliance_rate, 1);
    document.getElementById('metric-exception').textContent = fmtPercent(data.performance.exception_rate, 2);
    document.getElementById('metric-cod').textContent = fmtPercent(data.financial.cod_collection_rate, 1);
    document.getElementById('metric-active').textContent = fmtNumber(data.overview.active_shipments);

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

    const hasData = trends.shipments.length && trends.shipments.some(s => (s.total || s.delivered));
    document.getElementById('trends-empty').classList.toggle('hidden', !!hasData);
}

function updateStatusChart(statusData) {
    const delivered = statusData.delivered || 0;
    const inTransit = (statusData.in_transit || 0) + (statusData.out_for_delivery || 0);
    const pending = (statusData.pending || 0) + (statusData.booked || 0);
    const cancelled = (statusData.cancelled || 0) + (statusData.returned || 0);
    
    statusChart.data.datasets[0].data = [delivered, inTransit, pending, cancelled];
    statusChart.update();

    const hasData = delivered || inTransit || pending || cancelled;
    document.getElementById('status-empty').classList.toggle('hidden', !!hasData);
}

function updateRevenueChart(trends) {
    revenueChart.data.labels = trends.revenue.map(r => r.period);
    revenueChart.data.datasets[0].data = trends.revenue.map(r => r.amount);
    revenueChart.update();

    const hasData = trends.revenue.length && trends.revenue.some(r => r.amount);
    document.getElementById('revenue-empty').classList.toggle('hidden', !!hasData);
}

function updateBranchTable(branches) {
    const tbody = document.querySelector('#branch-performance-table tbody');
    
    if (!branches.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = branches.map(branch => {
        const rateColor = branch.delivery_rate >= 90 ? '#22c55e' : branch.delivery_rate >= 70 ? '#facc15' : '#f43f5e';
        return `
        <tr class="table-row">
            <td class="table-cell-label">${branch.name}</td>
            <td class="table-cell-number">${fmtNumber(branch.total_shipments)}</td>
            <td class="table-cell-number">${fmtNumber(branch.delivered)}</td>
            <td class="table-cell-label">
                <div class="flex items-center gap-3">
                    <div class="relative h-2 w-28 rounded-full bg-white/10 overflow-hidden">
                        <div class="absolute left-0 top-0 h-full rounded-full" style="width: ${branch.delivery_rate}%; background-color: ${rateColor};"></div>
                    </div>
                    <span class="text-xs text-slate-300">${branch.delivery_rate}%</span>
                </div>
            </td>
            <td class="table-cell-number">${fmtCurrency(branch.revenue)}</td>
            <td class="table-cell-number">${fmtHours(branch.avg_delivery_hours, 1)}</td>
        </tr>
        `;
    }).join('');
}
</script>
@endpush
