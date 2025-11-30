<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Track Your Shipment - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .tracking-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="text-white">
    {{-- Header --}}
    <header class="py-6 px-4 border-b border-white/10">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold">{{ config('app.name') }}</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm text-slate-400 hover:text-white transition">
                &larr; Back to Home
            </a>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="max-w-4xl mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Track Your Shipment</h1>
            <p class="text-slate-400 text-lg">Enter your tracking number to see the current status of your delivery</p>
        </div>

        {{-- Tracking Form --}}
        <div class="glass-card rounded-2xl p-8 max-w-2xl mx-auto">
            <form action="{{ route('tracking.track') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label for="tracking_number" class="block text-sm font-medium text-slate-300 mb-2">
                        Tracking Number
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                               name="tracking_number" 
                               id="tracking_number"
                               value="{{ old('tracking_number', $tracking_number ?? '') }}"
                               placeholder="Enter tracking number, waybill, or reference"
                               class="tracking-input w-full pl-12 pr-4 py-4 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 transition text-lg"
                               required
                               autofocus>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">
                        Example: TRK-ABC123XYZ, BC-12345678
                    </p>
                </div>

                @if(isset($error))
                    <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $error }}</span>
                    </div>
                @endif

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition transform hover:scale-[1.02] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    Track Shipment
                </button>
            </form>
        </div>

        {{-- Features --}}
        <div class="grid md:grid-cols-3 gap-6 mt-16">
            <div class="text-center p-6">
                <div class="w-14 h-14 bg-sky-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Real-Time Updates</h3>
                <p class="text-sm text-slate-400">Get instant updates on your shipment's location and status</p>
            </div>
            <div class="text-center p-6">
                <div class="w-14 h-14 bg-purple-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Delivery Notifications</h3>
                <p class="text-sm text-slate-400">Subscribe to receive alerts for status changes</p>
            </div>
            <div class="text-center p-6">
                <div class="w-14 h-14 bg-emerald-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Proof of Delivery</h3>
                <p class="text-sm text-slate-400">View signature and photo confirmation upon delivery</p>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-8 px-4 mt-16">
        <div class="max-w-4xl mx-auto text-center text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <div class="mt-2 space-x-4">
                <a href="#" class="hover:text-white transition">Privacy Policy</a>
                <a href="#" class="hover:text-white transition">Terms of Service</a>
                <a href="#" class="hover:text-white transition">Contact Support</a>
            </div>
        </div>
    </footer>
</body>
</html>
