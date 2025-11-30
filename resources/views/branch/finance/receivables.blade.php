<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card bg-gray-800 border-gray-700">
            <div class="card-header bg-gray-900 border-gray-700">
                <h5 class="mb-0 text-white">Aging Analysis</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-green-500/10 rounded">
                            <small class="text-gray-400">Current</small>
                            <h4 class="text-green-400 mb-0">{{ $defaultCurrency }} {{ number_format($aging['current'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-yellow-500/10 rounded">
                            <small class="text-gray-400">1-15 Days</small>
                            <h4 class="text-yellow-400 mb-0">{{ $defaultCurrency }} {{ number_format($aging['bucket_1_15'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-orange-500/10 rounded">
                            <small class="text-gray-400">16-30 Days</small>
                            <h4 class="text-orange-400 mb-0">{{ $defaultCurrency }} {{ number_format($aging['bucket_16_30'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-red-500/10 rounded">
                            <small class="text-gray-400">31+ Days</small>
                            <h4 class="text-red-400 mb-0">{{ $defaultCurrency }} {{ number_format($aging['bucket_31_plus'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-gray-800 border-gray-700">
    <div class="card-header bg-gray-900 border-gray-700">
        <h5 class="mb-0 text-white">Top Debtors</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th class="text-end">Total Outstanding</th>
                        <th class="text-end">Invoice Count</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topDebtors ?? [] as $index => $debtor)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $debtor->name }}</td>
                        <td class="text-end">
                            <span class="badge bg-danger">{{ $defaultCurrency }} {{ number_format($debtor->total_outstanding, 2) }}</span>
                        </td>
                        <td class="text-end">{{ $debtor->invoice_count }}</td>
                        <td>
                            <a href="{{ route('branch.clients') }}?customer_id={{ $debtor->id }}" class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 py-4">No outstanding receivables</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
