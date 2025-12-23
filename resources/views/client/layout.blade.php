<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }} Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/client.css', 'resources/js/branch.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-zinc-950 text-white min-h-screen">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-zinc-900 border-r border-white/10 transform transition-transform duration-200 lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            {{-- Logo --}}
            <div class="h-16 flex items-center px-6 border-b border-white/10">
                <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 brand-gradient rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-lg">{{ config('app.name') }}</div>
                        <div class="text-xs text-zinc-500">Customer Portal</div>
                    </div>
                </a>
            </div>

            {{-- Navigation --}}
            <nav class="p-4 space-y-1">
                <a href="{{ route('client.dashboard') }}" class="sidebar-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('client.shipments.create') }}" class="sidebar-link {{ request()->routeIs('client.shipments.create') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Shipment
                </a>
                <a href="{{ route('client.shipments.index') }}" class="sidebar-link {{ request()->routeIs('client.shipments.*') && !request()->routeIs('client.shipments.create') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    My Shipments
                </a>
                <a href="{{ route('client.tracking') }}" class="sidebar-link {{ request()->routeIs('client.tracking') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Track Shipment
                </a>
                <a href="{{ route('client.quotes') }}" class="sidebar-link {{ request()->routeIs('client.quotes') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Get Quote
                </a>
                
                <div class="pt-4 mt-4 border-t border-white/10">
                    <div class="px-4 py-2 text-xs text-zinc-500 uppercase font-medium">Account</div>
                </div>
                
                <a href="{{ route('client.addresses') }}" class="sidebar-link {{ request()->routeIs('client.addresses') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Address Book
                </a>
                <a href="{{ route('client.invoices') }}" class="sidebar-link {{ request()->routeIs('client.invoices') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Invoices
                </a>
                <a href="{{ route('client.profile') }}" class="sidebar-link {{ request()->routeIs('client.profile') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </a>
                <a href="{{ route('client.support') }}" class="sidebar-link {{ request()->routeIs('client.support') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Support
                </a>
            </nav>

            {{-- User Card --}}
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center font-bold">
                        {{ strtoupper(substr(auth('customer')->user()->contact_person ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ auth('customer')->user()->contact_person }}</div>
                        <div class="text-xs text-zinc-500 truncate">{{ auth('customer')->user()->customer_code }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('client.logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

        {{-- Main Content --}}
        <div class="flex-1 lg:ml-64">
            {{-- Top Bar --}}
            <header class="h-16 bg-zinc-900/50 backdrop-blur-xl border-b border-white/10 flex items-center justify-between px-6 sticky top-0 z-30">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-white/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-semibold">@yield('header', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-4">
                    {{-- Quick Track --}}
                    <form action="{{ route('client.tracking') }}" method="GET" class="hidden md:flex items-center gap-2">
                        <input type="text" name="awb" placeholder="Track shipment..." class="w-48 bg-zinc-800 border border-white/10 rounded-lg px-3 py-1.5 text-sm placeholder-zinc-500 focus:border-red-500 outline-none">
                        <button type="submit" class="p-1.5 rounded-lg hover:bg-white/10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </form>
                    {{-- Notifications --}}
                    <button class="p-2 rounded-lg hover:bg-white/10 relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="p-6">
                @if(session('success'))
                    <div class="mb-6 bg-emerald-500/20 border border-emerald-500/30 rounded-lg p-4 flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-emerald-400">{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-6 bg-rose-500/20 border border-rose-500/30 rounded-lg p-4 flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-rose-400">{{ session('error') }}</span>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
