@extends('branch.layout')

@section('title', 'Expense Management')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Expense Management</h1>
        <p class="text-sm muted">Track and manage branch expenses</p>
    </div>
    <button onclick="openExpenseModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Expense
    </button>
</div>

@include('branch.finance._nav')

{{-- Date Range Filter --}}
<div class="glass-panel p-4 mb-6">
    <form class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Start Date</label>
            <input type="date" name="start" value="{{ $startDate }}" class="bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">End Date</label>
            <input type="date" name="end" value="{{ $endDate }}" class="bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Category</label>
            <select name="category" class="bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium text-sm">Filter</button>
    </form>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        {{-- Expenses Table --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Expense Records</div>
            <div class="overflow-x-auto rounded-lg border border-white/10">
                <table class="w-full">
                    <thead class="bg-zinc-800/50">
                        <tr class="text-left text-xs uppercase text-zinc-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3">Receipt #</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($expense->expense_date)->format('M d, Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-zinc-700">{{ $expense->category }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $expense->description }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-rose-400">{{ $defaultCurrency }} {{ number_format($expense->amount, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-400">{{ $expense->receipt_number ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No expenses found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($expenses->hasPages())
                <div class="mt-4">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        {{-- Summary by Category --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">By Category</div>
            <div class="space-y-3">
                @php $totalExpenses = collect($totalByCategory)->sum(); @endphp
                @foreach($categories as $cat)
                    @php $amount = $totalByCategory[$cat] ?? 0; @endphp
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm">{{ $cat }}</span>
                            <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($amount, 0) }}</span>
                        </div>
                        @if($totalExpenses > 0)
                            <div class="w-full h-1.5 bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-rose-500 rounded-full" style="width: {{ ($amount / $totalExpenses) * 100 }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
                <div class="pt-3 border-t border-white/10">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Total</span>
                        <span class="text-xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($totalExpenses, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Expense Modal --}}
<div id="expenseModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeExpenseModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="glass-panel w-full max-w-md">
            <form method="POST" action="{{ route('branch.finance.expenses.store') }}">
                @csrf
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Add Expense</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Category <span class="text-rose-400">*</span></label>
                            <select name="category" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Description <span class="text-rose-400">*</span></label>
                            <input type="text" name="description" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Amount <span class="text-rose-400">*</span></label>
                            <input type="number" name="amount" step="0.01" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Date <span class="text-rose-400">*</span></label>
                            <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Receipt Number</label>
                            <input type="text" name="receipt_number" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="w-full bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                    <button type="button" onclick="closeExpenseModal()" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-sm font-medium">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openExpenseModal() {
    document.getElementById('expenseModal').classList.remove('hidden');
}
function closeExpenseModal() {
    document.getElementById('expenseModal').classList.add('hidden');
}
</script>
@endsection
