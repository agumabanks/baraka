@extends('admin.layout')

@section('title', 'Finance - Accounts')
@section('header', 'Finance Management')

@section('content')
    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-2 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Accounts</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_accounts']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Balance</div>
            <div class="text-2xl font-bold">${{ number_format($stats['total_balance'], 2) }}</div>
        </div>
    </div>

    {{-- Accounts List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="text-sm font-semibold">All Accounts</div>
            <div class="flex gap-2">
                <a href="{{ route('admin.finance.transactions') }}" class="btn btn-sm btn-secondary">Trans

actions</a>
                <a href="{{ route('admin.finance.statements') }}" class="btn btn-sm btn-secondary">Statements</a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Account Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold">{{ $account->name }}</td>
                            <td class="px-4 py-3 text-sm muted">{{ $account->type ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm">
                                ${{ number_format($account->balance ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-sm badge-success">Active</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center muted">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
@endsection
