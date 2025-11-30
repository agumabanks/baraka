@extends('admin.layout')

@section('title', 'Branches')
@section('header', 'Branch Management')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div class="text-sm muted">Manage branch and hub records across the network.</div>
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Add Branch</a>
    </div>

    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-3 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Branches</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Active</div>
            <div class="text-2xl font-bold">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Hubs</div>
            <div class="text-2xl font-bold">{{ number_format($stats['hubs']) }}</div>
        </div>
    </div>

    {{-- Branch List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="text-sm font-semibold">All Branches</div>
            <a href="{{ route('admin.branches.create') }}" class="btn btn-sm btn-primary">Add Branch</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($branches as $branch)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.branches.show', $branch) }}" class="font-semibold text-sky-400 hover:text-sky-300">
                                    {{ $branch->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm muted">{{ $branch->branch_code }}</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-sm {{ $branch->branch_type === 'hub' ? 'badge-primary' : 'badge-secondary' }}">
                                    {{ ucfirst($branch->branch_type ?? 'branch') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm muted">{{ Str::limit($branch->address, 40) }}</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-sm {{ $branch->status ? 'badge-success' : 'badge-error' }}">
                                    {{ $branch->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.branches.edit', $branch) }}" class="text-sm text-amber-400 hover:text-amber-300">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center muted">No branches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($branches->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
@endsection
