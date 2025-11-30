@extends('admin.layout')

@section('title', 'Impersonation Logs')
@section('header', 'Impersonation Logs')

@section('content')
    <div class="grid gap-3 md:grid-cols-4">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Sessions</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_sessions']) }}</div>
        </div>
        <div class="stat-card border-amber-500/30">
            <div class="muted text-xs uppercase">Active Now</div>
            <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['active_sessions']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Today</div>
            <div class="text-2xl font-bold">{{ number_format($stats['today']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">This Month</div>
            <div class="text-2xl font-bold">{{ number_format($stats['this_month']) }}</div>
        </div>
    </div>

    <div class="glass-panel p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-lg font-semibold">Impersonation History</h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="dhl-table text-left w-full">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Target User</th>
                        <th>Started At</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td data-label="Admin">
                                <div class="font-medium text-white">{{ $log->admin_name }}</div>
                                <div class="text-xs muted">{{ $log->admin_email }}</div>
                            </td>
                            <td data-label="Target User">
                                <div class="font-medium text-white">{{ $log->target_name }}</div>
                                <div class="text-xs muted">{{ $log->target_email }}</div>
                            </td>
                            <td class="text-sm" data-label="Started At">
                                {{ \Carbon\Carbon::parse($log->started_at)->format('M d, Y H:i') }}
                            </td>
                            <td class="text-sm" data-label="Duration">
                                @if($log->ended_at)
                                    {{ \Carbon\Carbon::parse($log->started_at)->diffForHumans(\Carbon\Carbon::parse($log->ended_at), true) }}
                                @else
                                    <span class="text-amber-400">Ongoing</span>
                                @endif
                            </td>
                            <td data-label="Status">
                                @if($log->status === 'started')
                                    <span class="badge bg-amber-500/20 text-amber-400 border border-amber-500/30">Active</span>
                                @else
                                    <span class="badge bg-white/10 text-slate-300">Ended</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 muted">No logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="pt-4 border-t border-white/5">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
