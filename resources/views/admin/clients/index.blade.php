@extends('admin.layout')

@section('title', 'Client Management')
@section('header', 'Client Management')

@section('content')
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Total Clients</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Active</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">VIP Clients</div>
            <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['vip']) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Credit Issues</div>
            <div class="text-2xl font-bold text-rose-400">{{ number_format($stats['credit_issues']) }}</div>
        </div>
    </div>

    <div class="glass-panel p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
            <div>
                <div class="text-lg font-semibold">All Clients</div>
                <p class="text-sm muted">View and manage clients across all branches</p>
            </div>
            <form method="GET" action="{{ route('admin.clients.index') }}" class="flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search clients..."
                    class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                <select name="branch_id" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($branchFilter == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" @selected($statusFilter === 'active')>Active</option>
                    <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
                    <option value="suspended" @selected($statusFilter === 'suspended')>Suspended</option>
                    <option value="blacklisted" @selected($statusFilter === 'blacklisted')>Blacklisted</option>
                </select>
                <select name="customer_type" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="vip" @selected($typeFilter === 'vip')>VIP</option>
                    <option value="regular" @selected($typeFilter === 'regular')>Regular</option>
                    <option value="prospect" @selected($typeFilter === 'prospect')>Prospect</option>
                </select>
                <button type="submit" class="chip">Filter</button>
                @if($search || $branchFilter || $statusFilter || $typeFilter)
                    <a href="{{ route('admin.clients.index') }}" class="chip bg-slate-700">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-card">
            <table class="dhl-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Shipments</th>
                        <th>Credit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $customer->company_name ?: $customer->contact_person }}</div>
                                <div class="text-2xs muted">{{ $customer->customer_code }}</div>
                                @if($customer->email)
                                    <div class="text-2xs muted">{{ $customer->email }}</div>
                                @endif
                            </td>
                            <td>
                                @if($customer->primaryBranch)
                                    <span class="chip text-2xs">{{ $customer->primaryBranch->name }}</span>
                                @else
                                    <span class="muted text-2xs">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                        'inactive' => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
                                        'suspended' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                        'blacklisted' => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded text-2xs border {{ $statusColors[$customer->status] ?? $statusColors['inactive'] }}">
                                    {{ ucfirst($customer->status ?? 'unknown') }}
                                </span>
                            </td>
                            <td>
                                @if($customer->customer_type === 'vip')
                                    <span class="inline-flex px-2 py-0.5 rounded text-2xs bg-amber-500/20 text-amber-400 border border-amber-500/30">VIP</span>
                                @else
                                    <span class="text-2xs muted">{{ ucfirst($customer->customer_type ?? 'regular') }}</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                <div>{{ number_format($customer->shipments_count) }} shipments</div>
                                <div class="text-2xs muted">{{ number_format($customer->invoices_count) }} invoices</div>
                            </td>
                            <td class="text-sm">
                                <div>Limit: {{ number_format($customer->credit_limit ?? 0, 2) }}</div>
                                <div class="text-2xs {{ ($customer->current_balance ?? 0) > ($customer->credit_limit ?? 0) ? 'text-rose-400' : 'muted' }}">
                                    Balance: {{ number_format($customer->current_balance ?? 0, 2) }}
                                </div>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="{{ route('admin.clients.show', $customer) }}" class="chip text-2xs">View</a>
                                    <a href="{{ route('admin.clients.edit', $customer) }}" class="chip text-2xs bg-slate-700">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 muted">No clients found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>
@endsection
