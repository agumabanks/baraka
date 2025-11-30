@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Reports</h1>
                <a href="{{ route('admin.analytics.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Report Type Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 report-card" data-report="shipment">
                <div class="card-body text-center">
                    <i class="fas fa-box fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Shipment Report</h5>
                    <p class="card-text text-muted">Detailed shipment data with status, routes, and delivery information.</p>
                    <button class="btn btn-primary" onclick="showReportForm('shipment')">Generate</button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 report-card" data-report="financial">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Financial Report</h5>
                    <p class="card-text text-muted">Revenue, COD collection, invoicing, and financial metrics.</p>
                    <button class="btn btn-success" onclick="showReportForm('financial')">Generate</button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 report-card" data-report="performance">
                <div class="card-body text-center">
                    <i class="fas fa-tachometer-alt fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Performance Report</h5>
                    <p class="card-text text-muted">Delivery rates, SLA compliance, driver and branch performance.</p>
                    <button class="btn btn-info" onclick="showReportForm('performance')">Generate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Form -->
    <div class="card mb-4" id="report-form-card" style="display: none;">
        <div class="card-header">
            <h5 class="mb-0" id="report-form-title">Generate Report</h5>
        </div>
        <div class="card-body">
            <form id="report-form">
                <input type="hidden" id="report-type" name="type">
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date Range</label>
                        <select class="form-select" id="preset" name="preset">
                            @foreach($presets as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3" id="custom-start" style="display: none;">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    
                    <div class="col-md-4 mb-3" id="custom-end" style="display: none;">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </div>
                
                <!-- Shipment-specific filters -->
                <div id="shipment-filters" class="row" style="display: none;">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
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
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Payment Type</label>
                        <select class="form-select" name="payment_type">
                            <option value="">All Types</option>
                            <option value="prepaid">Prepaid</option>
                            <option value="cod">COD</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" id="format" name="format">
                            <option value="view">View in Browser</option>
                            <option value="csv">Download CSV</option>
                            <option value="xlsx">Download Excel</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="hideReportForm()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Results -->
    <div class="card" id="report-results" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Report Results</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="exportReport('xlsx')">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Summary Section -->
            <div id="report-summary" class="row mb-4"></div>
            
            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="report-table">
                    <thead class="thead-dark"></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="mb-0">Generating report...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentReportType = '';
let currentReportData = null;

document.getElementById('preset').addEventListener('change', function() {
    const custom = this.value === 'custom';
    document.getElementById('custom-start').style.display = custom ? 'block' : 'none';
    document.getElementById('custom-end').style.display = custom ? 'block' : 'none';
});

document.getElementById('report-form').addEventListener('submit', function(e) {
    e.preventDefault();
    generateReport();
});

function showReportForm(type) {
    currentReportType = type;
    document.getElementById('report-type').value = type;
    document.getElementById('report-form-title').textContent = `Generate ${type.charAt(0).toUpperCase() + type.slice(1)} Report`;
    document.getElementById('report-form-card').style.display = 'block';
    document.getElementById('report-results').style.display = 'none';
    
    // Show/hide type-specific filters
    document.getElementById('shipment-filters').style.display = type === 'shipment' ? 'flex' : 'none';
    
    // Scroll to form
    document.getElementById('report-form-card').scrollIntoView({ behavior: 'smooth' });
}

function hideReportForm() {
    document.getElementById('report-form-card').style.display = 'none';
}

function generateReport() {
    const form = document.getElementById('report-form');
    const formData = new FormData(form);
    const format = formData.get('format');
    
    // Build params
    const params = new URLSearchParams();
    for (const [key, value] of formData) {
        if (value) params.append(key, value);
    }
    
    if (format !== 'view') {
        // Export directly
        exportReport(format);
        return;
    }
    
    // Show loading
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
    
    const endpoint = `/admin/analytics/reports/${currentReportType}`;
    
    fetch(`${endpoint}?${params}`)
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.success) {
                currentReportData = data.data;
                displayReport(data.data);
            } else {
                alert('Error generating report: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            modal.hide();
            alert('Error: ' + error.message);
        });
}

function displayReport(data) {
    document.getElementById('report-results').style.display = 'block';
    
    // Display summary
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
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center py-3">
                            <div class="text-muted small">${label}</div>
                            <div class="h5 mb-0">${formattedValue}</div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    // Display table
    const tableData = data.data || data.daily_revenue || data.driver_performance || [];
    const thead = document.querySelector('#report-table thead');
    const tbody = document.querySelector('#report-table tbody');
    
    if (!tableData.length) {
        thead.innerHTML = '';
        tbody.innerHTML = '<tr><td class="text-center">No data available</td></tr>';
        return;
    }
    
    // Build headers
    const headers = Object.keys(tableData[0]);
    thead.innerHTML = '<tr>' + headers.map(h => 
        `<th>${h.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</th>`
    ).join('') + '</tr>';
    
    // Build rows
    tbody.innerHTML = tableData.slice(0, 100).map(row => 
        '<tr>' + headers.map(h => `<td>${row[h] ?? '-'}</td>`).join('') + '</tr>'
    ).join('');
    
    if (tableData.length > 100) {
        tbody.innerHTML += `<tr><td colspan="${headers.length}" class="text-center text-muted">
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
    
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
    
    fetch(`/admin/analytics/export?${params}`)
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.success && data.download_url) {
                window.location.href = data.download_url;
            } else {
                alert('Export failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            modal.hide();
            alert('Error: ' + error.message);
        });
}
</script>
@endpush
