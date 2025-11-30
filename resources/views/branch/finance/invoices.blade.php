<div class="card bg-gray-800 border-gray-700">
    <div class="card-header bg-gray-900 border-gray-700">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0 text-white">All Invoices</h5>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="view" value="invoices">
                    <select name="status" class="form-select form-select-sm bg-gray-900 border-gray-700 text-white">
                        <option value="">All Statuses</option>
                        <option value="1" {{ $statusFilter == 1 ? 'selected' : '' }}>Draft</option>
                        <option value="2" {{ $statusFilter == 2 ? 'selected' : '' }}>Finalized</option>
                        <option value="3" {{ $statusFilter == 3 ? 'selected' : '' }}>Paid</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->created_at)->format('M d, Y') }}</td>
                        <td class="text-end">{{ $defaultCurrency }} {{ number_format($invoice->total_charge, 2) }}</td>
                        <td class="text-end">{{ $defaultCurrency }} {{ number_format($invoice->current_payable, 2) }}</td>
                        <td>
                            @if($invoice->status == 1)
                                <span class="badge bg-secondary">Draft</span>
                            @elseif($invoice->status == 2)
                                <span class="badge bg-warning">Finalized</span>
                            @elseif($invoice->status == 3)
                                <span class="badge bg-success">Paid</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">View</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-4">No invoices found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="mt-3">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
