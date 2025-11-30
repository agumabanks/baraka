@extends('admin.layout')

@section('title', 'Delivery Personnel')
@section('header', 'Delivery Personnel Management')

@section('content')
    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-2 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Personnel</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Active</div>
            <div class="text-2xl font-bold">{{ number_format($stats['active']) }}</div>
        </div>
    </div>

    {{-- Personnel List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10">
            <div class="text-sm font-semibold">All Delivery Personnel</div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Hub</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($personnel as $person)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.delivery-personnel.show', $person) }}" class="font-semibold text-sky-400 hover:text-sky-300">
                                    {{ $person->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">{{ $person->phone }}</div>
                                <div class="text-xs muted">{{ $person->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm muted">{{ $person->hub->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-sm {{ $person->status ? 'badge-success' : 'badge-error' }}">
                                    {{ $person->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.delivery-personnel.show', $person) }}" class="text-sm text-sky-400 hover:text-sky-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center muted">No delivery personnel found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($personnel->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $personnel->links() }}
            </div>
        @endif
    </div>
@endsection
