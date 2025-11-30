@extends('admin.layout')

@section('title', 'Merchants')
@section('header', 'Merchant Management')

@section('content')
    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-2 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Merchants</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Active</div>
            <div class="text-2xl font-bold">{{ number_format($stats['active']) }}</div>
        </div>
    </div>

    {{-- Merchant List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10">
            <div class="text-sm font-semibold">All Merchants</div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Business Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Parcels</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($merchants as $merchant)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.merchants.show', $merchant) }}" class="font-semibold text-sky-400 hover:text-sky-300">
                                    {{ $merchant->business_name ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">{{ $merchant->name }}</div>
                                <div class="text-xs muted">{{ $merchant->phone }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-primary">{{ number_format($merchant->parcels_count ?? 0) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-sm {{ $merchant->status ? 'badge-success' : 'badge-error' }}">
                                    {{ $merchant->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('admin.merchants.show', $merchant) }}" class="text-sm text-sky-400 hover:text-sky-300">View</a>
                                <a href="{{ route('admin.merchants.statements', $merchant) }}" class="text-sm text-purple-400 hover:text-purple-300">Statements</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center muted">No merchants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($merchants->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $merchants->links() }}
            </div>
        @endif
    </div>
@endsection
