{{-- Finance Navigation Tabs --}}
<div class="flex flex-wrap gap-2 mb-6 border-b border-white/10 pb-4">
    <a href="{{ route('branch.finance.index', ['view' => 'overview']) }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.index') && request('view', 'overview') === 'overview' ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        Overview
    </a>
    <a href="{{ route('branch.finance.index', ['view' => 'receivables']) }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.index') && request('view') === 'receivables' ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        Receivables
    </a>
    <a href="{{ route('branch.finance.index', ['view' => 'invoices']) }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.index') && request('view') === 'invoices' ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        Invoices
    </a>
    <a href="{{ route('branch.finance.cod') }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.cod') ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        COD Management
    </a>
    <a href="{{ route('branch.finance.expenses') }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.expenses') ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        Expenses
    </a>
    <a href="{{ route('branch.finance.cash-position') }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.cash-position') ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        Cash Position
    </a>
    <a href="{{ route('branch.finance.daily-report') }}" 
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('branch.finance.daily-report') ? 'bg-emerald-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700' }}">
        Daily Report
    </a>
</div>
