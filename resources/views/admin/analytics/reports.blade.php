@extends('admin.layout')

@section('title', 'Analytics Reports')
@section('header', 'Analytics Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <p class="muted">Generate comprehensive reports from your analytics data</p>
        <a href="{{ route('admin.analytics.dashboard') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Dashboard
        </a>
    </div>

    <!-- Report Type Cards -->
    <div class="grid gap-4 md:grid-cols-3">
        <div class="glass-panel p-6 text-center hover:border-sky-500/30 transition cursor-pointer group" onclick="showReportForm('shipment')">
            <div class="w-16 h-16 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <h3 class="text-lg font-semibold mb-2">Shipment Report</h3>
            <p class="text-sm muted mb-4">Detailed shipment data with status, routes, and delivery information.</p>
            <button class="btn btn-primary w-full justify-center">Generate</button>
        </div>
        
        <div class="glass-panel p-6 text-center hover:border-emerald-500/30 transition cursor-pointer group" onclick="showReportForm('financial')">
            <div class="w-16 h-16 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-lg font-semibold mb-2">Financial Report</h3>
            <p class="text-sm muted mb-4">Revenue, COD collection, invoicing, and financial metrics.</p>
            <button class="btn btn-primary w-full justify-center">Generate</button>
        </div>
        
        <div class="glass-panel p-6 text-center hover:border-purple-500/30 transition cursor-pointer group" onclick="showReportForm('performance')">
            <div class="w-16 h-16 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <h3 class="text-lg font-semibold mb-2">Performance Report</h3>
            <p class="text-sm muted mb-4">Delivery rates, SLA compliance, driver and branch performance.</p>
            <button class="btn btn-primary w-full justify-center">Generate</button>
        </div>
    </div>

    <!-- Report Form -->
    <div class="glass-panel p-6 hidden" id="report-form-card">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold" id="report-form-title">Generate Report</h3>
            <button type="button" class="text-slate-400 hover:text-white" onclick="hideReportForm()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="report-form" class="space-y-4">
            <input type="hidden" id="report-type" name="type">
            
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium mb-2">Date Range</label>
                    <select id="preset" name="preset" class="w-full bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
                        @foreach($presets ?? ['today' => 'Today', 'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'custom' => 'Custom Range'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="custom-start" class="hidden">
                    <label class="block text-sm font-medium mb-2">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="w-full bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
                </div>
                
                <div id="custom-end" class="hidden">
                    <label class="block text-sm font-medium mb-2">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="w-full bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
                </div>
            </div>
            
            <!-- Shipment-specific filters -->
            <div id="shipment-filters" class="hidden grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-2">Status</label>
                    <select name="status" class="w-full bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="picked_up">Picked Up</option>
                        <option value="in_transit">In Transit</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Payment Type</label>
                    <select name="payment_type" class="w-full bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
                        <option value="">All Types</option>
                        <option value="prepaid">Prepaid</option>
                        <option value="cod">COD</option>
                    </select>
                </div>
            </div>
            
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium mb-2">Export Format</label>
                    <select id="format" name="format" class="w-full bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50">
                        <option value="view">View in Browser</option>
                        <option value="csv">Download CSV</option>
                        <option value="xlsx">Download Excel</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-white/10">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Generate Report
                </button>
                <button type="button" class="btn btn-secondary" onclick="hideReportForm()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Report Results -->
    <div class="glass-panel p-6 hidden" id="report-results">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold">Report Results</h3>
            <div class="flex gap-2">
                <button class="btn btn-sm btn-secondary" onclick="exportReport('csv')">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    CSV
                </button>
                <button class="btn btn-sm btn-secondary" onclick="exportReport('xlsx')">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel
                </button>
            </div>
        </div>
        
        <!-- Summary Section -->
        <div id="report-summary" class="grid gap-4 md:grid-cols-4 mb-6"></div>
        
        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="report-table">
                <thead>
                    <tr class="border-b border-white/10"></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="glass-panel p-8 text-center">
            <div class="animate-spin w-12 h-12 border-4 border-emerald-500/30 border-t-emerald-500 rounded-full mx-auto mb-4"></div>
            <p class="text-sm">Generating report...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentReportType = '';
let currentReportData = null;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('preset').addEventListener('change', function() {
        const custom = this.value === 'custom';
        document.getElementById('custom-start').classList.toggle('hidden', !custom);
        document.getElementById('custom-end').classList.toggle('hidden', !custom);
    });

    document.getElementById('report-form').addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
});

function showReportForm(type) {
    currentReportType = type;
    document.getElementById('report-type').value = type;
    document.getElementById('report-form-title').textContent = `Generate ${type.charAt(0).toUpperCase() + type.slice(1)} Report`;
    document.getElementById('report-form-card').classList.remove('hidden');
    document.getElementById('report-results').classList.add('hidden');
    
    document.getElementById('shipment-filters').classList.toggle('hidden', type !== 'shipment');
    
    document.getElementById('report-form-card').scrollIntoView({ behavior: 'smooth' });
}

function hideReportForm() {
    document.getElementById('report-form-card').classList.add('hidden');
}

function generateReport() {
    const form = document.getElementById('report-form');
    const formData = new FormData(form);
    const format = formData.get('format');
    
    const params = new URLSearchParams();
    for (const [key, value] of formData) {
        if (value) params.append(key, value);
    }
    
    if (format !== 'view') {
        exportReport(format);
        return;
    }
    
    document.getElementById('loading-overlay').classList.remove('hidden');
    
    const endpoint = `/admin/analytics/reports/${currentReportType}`;
    
    fetch(`${endpoint}?${params}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading-overlay').classList.add('hidden');
            if (data.success) {
                currentReportData = data.data;
                displayReport(data.data);
            } else {
                alert('Error generating report: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            document.getElementById('loading-overlay').classList.add('hidden');
            alert('Error: ' + error.message);
        });
}

function displayReport(data) {
    document.getElementById('report-results').classList.remove('hidden');
    
    const summaryDiv = document.getElementById('report-summary');
    summaryDiv.innerHTML = '';
    
    if (data.summary) {
        Object.entries(data.summary).forEach(([key, value]) => {
            if (typeof value === 'object') return;
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            const formattedValue = typeof value === 'number' ? 
                (key.includes('rate') || key.includes('Rate') ? value + '%' : 
                 key.includes('revenue') || key.includes('amount') || key.includes('cod') ? 
                 '$' + value.toLocaleString() : value.toLocaleString()) : value;
            
            summaryDiv.innerHTML += `
                <div class="stat-card">
                    <div class="muted text-xs uppercase">${label}</div>
                    <div class="text-2xl font-bold">${formattedValue}</div>
                </div>
            `;
        });
    }
    
    const tableData = data.data || data.daily_revenue || data.driver_performance || [];
    const thead = document.querySelector('#report-table thead tr');
    const tbody = document.querySelector('#report-table tbody');
    
    if (!tableData.length) {
        thead.innerHTML = '';
        tbody.innerHTML = '<tr><td class="text-center py-8 muted">No data available</td></tr>';
        return;
    }
    
    const headers = Object.keys(tableData[0]);
    thead.innerHTML = headers.map(h => 
        `<th class="text-left py-3 px-4 text-xs uppercase muted">${h.replace(/_/g, ' ')}</th>`
    ).join('');
    
    tbody.innerHTML = tableData.slice(0, 100).map(row => 
        '<tr class="border-b border-white/5 hover:bg-white/5">' + 
        headers.map(h => `<td class="py-3 px-4">${row[h] ?? '-'}</td>`).join('') + 
        '</tr>'
    ).join('');
    
    if (tableData.length > 100) {
        tbody.innerHTML += `<tr><td colspan="${headers.length}" class="text-center py-4 muted">
            Showing first 100 of ${tableData.length} rows. Export for full data.
        </td></tr>`;
    }
    
    document.getElementById('report-results').scrollIntoView({ behavior: 'smooth' });
}

function exportReport(format) {
    const form = document.getElementById('report-form');
    const formData = new FormData(form);
    formData.set('format', format);
    formData.set('type', currentReportType);
    
    const params = new URLSearchParams();
    for (const [key, value] of formData) {
        if (value) params.append(key, value);
    }
    
    document.getElementById('loading-overlay').classList.remove('hidden');
    
    fetch(`/admin/analytics/export?${params}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading-overlay').classList.add('hidden');
            if (data.success && data.download_url) {
                window.location.href = data.download_url;
            } else {
                alert('Export failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            document.getElementById('loading-overlay').classList.add('hidden');
            alert('Error: ' + error.message);
        });
}
</script>
@endpush
