<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <small class="text-gray-400">Total Collected ({{ ucfirst($collectionPeriod) }})</small>
                <h3 class="text-white">{{ $defaultCurrency }} {{ number_format($totalCollected, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <small class="text-gray-400">Average Daily Collection</small>
                <h3 class="text-white">{{ $defaultCurrency }} {{ number_format($avgDailyCollection, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-body">
                <small class="text-gray-400">Collection Days</small>
                <h3 class="text-white">{{ count($dailyCollections) }} days</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-header bg-gray-900 border-gray-700 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Collection Trend</h5>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('branch.finance', ['view' => 'collections', 'period' => 'week']) }}" 
                       class="btn btn-sm {{ $collectionPeriod === 'week' ? 'btn-primary' : 'btn-outline-secondary' }}">Week</a>
                    <a href="{{ route('branch.finance', ['view' => 'collections', 'period' => 'month']) }}" 
                       class="btn btn-sm {{ $collectionPeriod === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">Month</a>
                    <a href="{{ route('branch.finance', ['view' => 'collections', 'period' => 'quarter']) }}" 
                       class="btn btn-sm {{ $collectionPeriod === 'quarter' ? 'btn-primary' : 'btn-outline-secondary' }}">Quarter</a>
                </div>
            </div>
            <div class="card-body">
                <canvas id="collectionTrendChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Collection Methods</h5>
            </div>
            <div class="card-body">
                @foreach($collectionMethods as $method)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-white">{{ $method['method'] }}</span>
                        <span class="text-gray-400">{{ $method['count'] }} txns</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: {{ ($method['amount'] / max($totalCollected, 1)) * 100 }}%">
                            {{ $defaultCurrency }} {{ number_format($method['amount'], 0) }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('collectionTrendChart');
if (ctx) {
    const data = @json($dailyCollections);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'Collections',
                data: data.map(d => d.collected),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
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
