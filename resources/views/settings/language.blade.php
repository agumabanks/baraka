@extends('settings.layouts.tailwind')

@section('title', trans_db('settings.language.title', [], null, 'Language & Translations'))

@section('content')
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ trans_db('settings.language.title', [], null, 'Language & Translations') }}
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">
                    {{ trans_db('settings.language.description', [], null, 'Manage interface languages and translation keys.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('settings.index') }}" class="btn-secondary">
                    <i class="bi bi-arrow-left mr-2"></i>
                    Back
                </a>
                <button type="submit" form="translationsForm" class="btn-primary shadow-lg shadow-blue-500/20">
                    <i class="bi bi-check-lg mr-2"></i>
                    Save All Changes
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Completion Stats for Each Language -->
            @foreach($supportedLocales as $lang)
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            {{ $locales[$lang] }}
                        </span>
                        <span class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ $stats[$lang]['percentage'] }}%
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 mb-3">
                        <div class="bg-blue-500 h-2 rounded-full transition-all duration-500 ease-out" 
                             style="width: {{ $stats[$lang]['percentage'] }}%"></div>
                    </div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ number_format($stats[$lang]['translated']) }} / {{ number_format($stats[$lang]['total']) }} keys translated
                    </div>
                </div>
            @endforeach

            <!-- Default Language Selector -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <label class="block text-sm font-medium text-slate-900 dark:text-white mb-4">Default Language</label>
                <div class="space-y-3">
                    @foreach($supportedLocales as $lang)
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative flex items-center justify-center">
                                <input type="radio" name="default_locale" value="{{ $lang }}" 
                                       {{ $defaultLocale === $lang ? 'checked' : '' }}
                                       class="peer sr-only">
                                <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 peer-checked:border-blue-500 peer-checked:bg-blue-500 transition-all"></div>
                                <div class="absolute w-2 h-2 rounded-full bg-white scale-0 peer-checked:scale-100 transition-transform"></div>
                            </div>
                            <span class="text-sm text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ $locales[$lang] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
            <form method="GET" action="{{ route('settings.language') }}" class="flex flex-col md:flex-row gap-4">
                <!-- Search Input -->
                <div class="flex-1">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-search text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                        </div>
                        <input type="search" name="q" value="{{ $search }}"
                               class="block w-full pl-11 pr-4 py-2.5 border-none bg-slate-50 dark:bg-slate-900 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 text-slate-900 dark:text-white placeholder-slate-400 transition-all"
                               placeholder="Search translation keys or values...">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="w-full md:w-56">
                    <div class="relative">
                        <select name="status" 
                                class="block w-full pl-4 pr-10 py-2.5 border-none bg-slate-50 dark:bg-slate-900 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-300 appearance-none cursor-pointer">
                            <option value="">All Statuses</option>
                            <option value="complete" {{ $statusFilter === 'complete' ? 'selected' : '' }}>✅ Complete</option>
                            <option value="incomplete" {{ $statusFilter === 'incomplete' ? 'selected' : '' }}>⚠️ Incomplete</option>
                            <option value="empty" {{ $statusFilter === 'empty' ? 'selected' : '' }}>❌ Empty</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                            <i class="bi bi-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-secondary">
                    <i class="bi bi-funnel mr-2"></i>Filter
                </button>
            </form>
        </div>

        <!-- Translations Form -->
        <form id="translationsForm" method="POST" action="{{ route('settings.language.update') }}" class="ajax-form">
            @csrf

            <!-- Translations Table -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-1/4">
                                    Key
                                </th>
                                @foreach($supportedLocales as $lang)
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-1/4">
                                        {{ $locales[$lang] }}
                                    </th>
                                @endforeach
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">
                                    <i class="bi bi-gear"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @forelse($translations as $key => $langs)
                                @php
                                    $filledCount = collect($langs)->filter(fn($l) => !empty($l['value']))->count();
                                    $totalLangs = count($supportedLocales);
                                    $statusClass = match(true) {
                                        $filledCount === $totalLangs => 'hover:bg-slate-50 dark:hover:bg-slate-700/50',
                                        $filledCount > 0 => 'bg-amber-50/30 dark:bg-amber-900/5 hover:bg-amber-50/50 dark:hover:bg-amber-900/10',
                                        default => 'bg-red-50/30 dark:bg-red-900/5 hover:bg-red-50/50 dark:hover:bg-red-900/10'
                                    };
                                @endphp
                                <tr class="group transition-colors {{ $statusClass }}">
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex flex-col gap-1">
                                            <code class="text-xs font-mono text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded w-fit select-all">{{ $key }}</code>
                                            <div class="flex items-center gap-2 mt-1">
                                                @if($filledCount === $totalLangs)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-1.5 py-0.5 rounded">
                                                        <i class="bi bi-check-circle-fill"></i> Complete
                                                    </span>
                                                @elseif($filledCount > 0)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-1.5 py-0.5 rounded">
                                                        <i class="bi bi-exclamation-circle-fill"></i> {{ $filledCount }}/{{ $totalLangs }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded">
                                                        <i class="bi bi-x-circle-fill"></i> Empty
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    @foreach($supportedLocales as $lang)
                                        <td class="px-6 py-4 align-top">
                                            <textarea name="translations[{{ $key }}][{{ $lang }}]" 
                                                      rows="2"
                                                      class="block w-full px-3 py-2 text-sm border-none bg-transparent focus:bg-white dark:focus:bg-slate-900 rounded-lg focus:ring-2 focus:ring-blue-500/20 transition-all resize-none placeholder-slate-300 dark:placeholder-slate-600"
                                                      placeholder="Enter translation...">{{ $langs[$lang]['value'] ?? '' }}</textarea>
                                        </td>
                                    @endforeach
                                    <td class="px-6 py-4 align-middle text-center">
                                        <button type="button" 
                                                onclick="deleteTranslation('{{ $key }}')"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all opacity-0 group-hover:opacity-100"
                                                title="Delete this translation">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($supportedLocales) + 2 }}" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                                <i class="bi bi-translate text-2xl"></i>
                                            </div>
                                            <h3 class="text-sm font-medium text-slate-900 dark:text-white">No translations found</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                                                Try adjusting your search or filter, or add a new translation key below.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Add New Translation Row -->
                            <tr class="bg-blue-50/30 dark:bg-blue-900/5 border-t border-blue-100 dark:border-blue-900/20">
                                <td class="px-6 py-4 align-top">
                                    <div class="space-y-1">
                                        <input type="text" 
                                               name="new_translation[key]" 
                                               class="block w-full px-3 py-2 text-sm border-none bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500/20 placeholder-slate-400"
                                               placeholder="e.g. dashboard.welcome">
                                        <p class="text-[10px] text-blue-600 dark:text-blue-400 font-medium pl-1">New Key</p>
                                    </div>
                                </td>
                                @foreach($supportedLocales as $lang)
                                    <td class="px-6 py-4 align-top">
                                        <textarea name="new_translation[{{ $lang }}]" 
                                                  rows="2"
                                                  class="block w-full px-3 py-2 text-sm border-none bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500/20 resize-none placeholder-slate-400"
                                                  placeholder="Value..."></textarea>
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 align-middle text-center">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20">
                                        <i class="bi bi-plus-lg"></i>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination and Actions -->
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        Showing {{ $translations->firstItem() ?? 0 }} to {{ $translations->lastItem() ?? 0 }} of {{ $totalCount }} keys
                    </div>
                    <div class="flex items-center gap-4">
                        {{ $translations->links() }}
                    </div>
                </div>
            </div>
        </form>

        <!-- Help Text -->
        <div class="mt-6 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 flex items-start gap-4">
            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                <i class="bi bi-lightbulb"></i>
            </div>
            <div class="text-sm text-slate-600 dark:text-slate-400">
                <h4 class="font-semibold text-slate-900 dark:text-white mb-1">Pro Tips</h4>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1 list-disc list-inside">
                    <li>Use dot notation for keys (e.g., <code class="px-1 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-xs">auth.login.title</code>)</li>
                    <li>Translations are cached automatically for performance</li>
                    <li>Empty values fall back to the default language</li>
                    <li>Use <code class="px-1 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-xs">:param</code> for dynamic values</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Auto-save default locale change
        document.querySelectorAll('input[name="default_locale"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('default_locale', this.value);

                fetch('{{ route('settings.language.update') }}', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Optional: Show toast
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        // Delete translation
        function deleteTranslation(key) {
            if (!confirm(`Delete translation key "${key}"? This cannot be undone.`)) {
                return;
            }

            fetch(`{{ route('settings.language.delete', ':key') }}`.replace(':key', encodeURIComponent(key)), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete translation');
            });
        }
    </script>
@endsection
