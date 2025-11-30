<div class="card bg-gray-800 border-gray-700">
    <div class="card-header bg-gray-900 border-gray-700">
        <h5 class="mb-0 text-white">Payment History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Payment Date</th>
                        <th class="text-end">Amount Paid</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->invoice_number }}</td>
                        <td>{{ $payment->customer_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->updated_at)->format('M d, Y H:i') }}</td>
                        <td class="text-end">
                            <span class="badge bg-success">{{ $defaultCurrency }} {{ number_format($payment->total_charge - $payment->current_payable, 2) }}</span>
                        </td>
                        <td><span class="text-gray-400">{{ $payment->invoice_id }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 py-4">No payments recorded</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="mt-3">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
