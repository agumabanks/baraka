<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans_db('tracking.not_found.title', [], null, 'Shipment Not Found') }} - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="text-white flex flex-col min-h-screen">
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
            <x-language-switcher style="dropdown" :show-flags="true" :show-labels="true" />
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 flex items-center justify-center px-4">
        <div class="text-center max-w-md">
            <div class="w-24 h-24 bg-amber-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-4">{{ trans_db('tracking.not_found.heading', [], null, 'Shipment Not Found') }}</h1>
            <p class="text-slate-400 mb-2">
                {{ trans_db('tracking.not_found.body', [], null, 'We couldn\\'t find a shipment with tracking number:') }}
            </p>
            <p class="font-mono text-lg text-sky-400 mb-8">{{ $tracking_number }}</p>
            <div class="space-y-3">
                <a href="{{ route('tracking.index') }}" 
                   class="block w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold py-3 px-6 rounded-xl transition">
                    {{ trans_db('tracking.not_found.try_another', [], null, 'Try Another Tracking Number') }}
                </a>
                <a href="mailto:support@{{ request()->host() }}" 
                   class="block w-full bg-white/10 hover:bg-white/20 border border-white/20 text-white py-3 px-6 rounded-xl transition">
                    {{ trans_db('tracking.not_found.contact_support', [], null, 'Contact Support') }}
                </a>
            </div>
            <div class="mt-8 text-sm text-slate-500">
                <p>{{ trans_db('tracking.not_found.hint1', [], null, 'Please check your tracking number and try again.') }}</p>
                <p class="mt-1">{{ trans_db('tracking.not_found.hint2', [], null, 'If you continue to have issues, please contact our support team.') }}</p>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-6 px-4">
        <div class="max-w-4xl mx-auto text-center text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
