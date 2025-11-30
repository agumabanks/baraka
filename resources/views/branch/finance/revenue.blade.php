<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Revenue Trend (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card bg-gray-800 border-gray-700">
    <div class="card-header bg-gray-900 border-gray-700">
        <h5 class="mb-0 text-white">Top 10 Revenue Customers</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th class="text-end">Total Revenue</th>
                        <th class="text-end">Invoices</th>
                        <th class="text-end">Avg per Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenueByCustomer as $index => $customer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $customer->name }}</td>
                        <td class="text-end">
                            <span class="badge bg-success">{{ $defaultCurrency }} {{ number_format($customer->total_revenue, 2) }}</span>
                        </td>
                        <td class="text-end">{{ $customer->invoice_count }}</td>
                        <td class="text-end">{{ $defaultCurrency }} {{ number_format($customer->total_revenue / max($customer->invoice_count, 1), 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 py-4">No revenue data available</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-top border-gray-600">
                        <th colspan="2">Total</th>
                        <th class="text-end">{{ $defaultCurrency }} {{ number_format($totalRevenue, 2) }}</th>
                        <th class="text-end">{{ $revenueByCustomer->sum('invoice_count') }}</th>
                        <th class="text-end">{{ $defaultCurrency }} {{ number_format($avgRevenuePerCustomer, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('revenueChart');
if (ctx) {
    const data = @json($monthlyRevenue);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => `${d.year}-${String(d.month).padStart(2, '0')}`),
            datasets: [{
                label: 'Revenue',
                data: data.map(d => d.revenue),
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } }
            }
        }
    });
}
</script>
@endpush
