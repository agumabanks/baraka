@extends('admin.layout')

@section('title', 'Reports')
@section('header', 'Reports Center')

@section('content')
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($reports as $report)
            <div class="glass-panel p-5 flex flex-col h-full hover:border-white/20 transition group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-slate-400 group-hover:text-white group-hover:bg-white/10 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <span class="badge {{ $report['status'] === 'Ready' ? 'badge-success' : 'badge-warn' }}">
                        {{ $report['status'] }}
                    </span>
                </div>
                
                <h3 class="text-lg font-semibold mb-2">{{ $report['name'] }}</h3>
                <p class="text-sm muted mb-4 flex-1">{{ $report['description'] }}</p>
                
                <div class="pt-4 border-t border-white/5 flex items-center justify-between text-xs">
                    <span class="muted">{{ $report['category'] }}</span>
                    <span class="text-slate-400">Updated {{ $report['last_generated']->diffForHumans() }}</span>
                </div>
                
                <div class="mt-4 flex gap-2">
                    <button class="btn btn-sm btn-primary w-full justify-center">View</button>
                    <button class="btn btn-sm btn-secondary w-full justify-center">Download</button>
                </div>
            </div>
        @endforeach
        
        <!-- Add New Report Placeholder -->
        <button class="glass-panel p-5 flex flex-col items-center justify-center h-full border-dashed border-white/20 hover:border-white/40 hover:bg-white/5 transition text-slate-400 hover:text-white gap-3 min-h-[200px]">
            <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span class="font-medium">Create Custom Report</span>
        </button>
    </div>
@endsection
