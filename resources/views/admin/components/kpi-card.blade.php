@props([
    'title' => '',
    'value' => '0',
    'subtitle' => '',
    'icon' => 'chart-bar',
    'color' => 'sky', // sky, emerald, amber, purple, rose
    'trend' => null, // +5.2% or -3.1%
    'href' => null,
])

@php
    $colorClasses = [
        'sky' => 'from-sky-500/10 to-sky-500/5 border-sky-500/20 text-sky-400',
        'emerald' => 'from-emerald-500/10 to-emerald-500/5 border-emerald-500/20 text-emerald-400',
        'amber' => 'from-amber-500/10 to-amber-500/5 border-amber-500/20 text-amber-400',
        'purple' => 'from-purple-500/10 to-purple-500/5 border-purple-500/20 text-purple-400',
        'rose' => 'from-rose-500/10 to-rose-500/5 border-rose-500/20 text-rose-400',
    ];
    $iconBgClasses = [
        'sky' => 'bg-sky-500/20',
        'emerald' => 'bg-emerald-500/20',
        'amber' => 'bg-amber-500/20',
        'purple' => 'bg-purple-500/20',
        'rose' => 'bg-rose-500/20',
    ];
@endphp

<div class="stat-card bg-gradient-to-r {{ $colorClasses[$color] ?? $colorClasses['sky'] }} border {{ $href ? 'cursor-pointer hover:bg-white/10 transition' : '' }}"
     @if($href) onclick="window.location='{{ $href }}'" @endif>
    <div class="flex items-center justify-between">
        <div>
            <div class="muted text-xs uppercase tracking-wider">{{ $title }}</div>
            <div class="text-3xl font-bold {{ str_replace('from-', 'text-', explode(' ', $colorClasses[$color] ?? '')[0]) }}">{{ $value }}</div>
            @if($subtitle)
                <div class="text-xs muted mt-1">{{ $subtitle }}</div>
            @endif
            @if($trend)
                <div class="text-xs mt-1 {{ str_starts_with($trend, '+') ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $trend }} vs last period
                </div>
            @endif
        </div>
        <div class="w-12 h-12 rounded-full {{ $iconBgClasses[$color] ?? $iconBgClasses['sky'] }} flex items-center justify-center">
            @switch($icon)
                @case('box')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    @break
                @case('truck')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                    @break
                @case('check')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @break
                @case('clock')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @break
                @case('currency')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @break
                @case('users')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    @break
                @case('building')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    @break
                @default
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            @endswitch
        </div>
    </div>
</div>
