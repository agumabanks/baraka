@extends('admin.layout')

@section('title', 'Hubs')
@section('header', 'Hub Management')

@section('content')
    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-2 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Hubs</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Active</div>
            <div class="text-2xl font-bold">{{ number_format($stats['active']) }}</div>
        </div>
    </div>

    {{-- Hub List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10">
            <div class="text-sm font-semibold">All Hubs</div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($hubs as $hub)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.hubs.show', $hub) }}" class="font-semibold text-sky-400 hover:text-sky-300">
                                    {{ $hub->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm muted">{{ $hub->branch_code }}</td>
                            <td class="px-4 py-3 text-sm muted">{{ Str::limit($hub->address, 50) }}</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-sm {{ $hub->status ? 'badge-success' : 'badge-error' }}">
                                    {{ $hub->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.hubs.show', $hub) }}" class="text-sm text-sky-400 hover:text-sky-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center muted">No hubs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hubs->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $hubs->links() }}
            </div>
        @endif
    </div>
@endsection
