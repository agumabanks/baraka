<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Branch Control Center') • {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/branch.css', 'resources/js/branch.js'])
    <style>[x-cloak] { display: none !important; }</style>
    @stack('styles')
</head>
<body class="h-screen overflow-hidden">
    <div class="flex h-screen bg-obsidian-900">
        <div class="fixed inset-0 bg-black/50 hidden" data-overlay></div>
        @include('branch.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-y-auto">
            <header class="sticky top-0 z-30 border-b border-white/10 bg-obsidian-800/80 backdrop-blur">
                <div class="px-4 lg:px-8 py-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3">
                        <button class="lg:hidden nav-link !px-2 !py-1" type="button" data-sidebar-toggle>Menu</button>
                        <div>
                            <div class="text-xs uppercase muted">Active branch</div>
                            <div class="text-xl font-semibold leading-tight">{{ $branch->name ?? 'Branch' }}</div>
                            @if($branch?->parent)
                                <div class="muted text-xs">Parent: {{ $branch->parent->code }} • {{ $branch->parent->name }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if(!empty($branchOptions))
                            <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
                                @foreach(request()->except('branch_id') as $k => $v)
                                    @if(is_scalar($v))
                                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                    @endif
                                @endforeach
                                <select name="branch_id" class="bg-obsidian-700 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/30 text-white" data-auto-submit>
                                    @foreach($branchOptions as $opt)
                                        <option value="{{ $opt->id }}" @selected(($branch->id ?? null) === $opt->id)>{{ $opt->code }} — {{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <x-language-switcher style="dropdown" :show-flags="true" :show-labels="true" />
                        <div class="text-right">
                            <div class="muted text-2xs">Signed in as</div>
                            <div class="text-sm font-semibold">{{ auth()->user()->name }}</div>
                        </div>
                    </div>
                </div>
            </header>
            <main class="flex-1 px-4 lg:px-8 py-6 space-y-6">
                @if(session('success'))
                    <div class="glass-panel px-4 py-3 border-emerald-500/30 text-emerald-100">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="glass-panel px-4 py-3 border-rose-500/30 text-rose-100">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="glass-panel px-4 py-3 border-amber-500/30 text-amber-100">
                        <div class="font-semibold">Please fix the following:</div>
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
