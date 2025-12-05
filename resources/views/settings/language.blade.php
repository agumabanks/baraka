@extends('settings.layouts.tailwind')

@section('title', trans_db('settings.language.title', [], null, 'Language & Translations'))

@section('content')
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ trans_db('settings.language.title', [], null, 'Language & Translations') }}
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">
                    {{ trans_db('settings.language.description', [], null, 'Manage interface languages, translation keys, and localization settings.') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Global Language Switcher -->
                <x-language-switcher style="dropdown" />
                
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Completion Stats for Each Language -->
            @foreach($supportedLocales as $lang)
                @php
                    $meta = $localesWithMeta[$lang] ?? ['name' => $lang, 'native' => $lang, 'rtl' => false, 'flag' => 'un'];
                    $isRtl = $meta['rtl'] ?? false;
                @endphp
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700 {{ $isRtl ? 'border-l-4 border-l-amber-400' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $stats[$lang]['percentage'] >= 90 ? 'bg-green-500' : ($stats[$lang]['percentage'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                            {{ $meta['native'] }}
                            @if($isRtl)
                                <span class="text-[10px] px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">RTL</span>
                            @endif
                            @if($lang === $defaultLocale)
                                <span class="text-[10px] px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">Default</span>
                            @endif
                        </span>
                        <span class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ $stats[$lang]['percentage'] }}%
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 mb-3">
                        <div class="h-2 rounded-full transition-all duration-500 ease-out {{ $stats[$lang]['percentage'] >= 90 ? 'bg-green-500' : ($stats[$lang]['percentage'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" 
                             style="width: {{ $stats[$lang]['percentage'] }}%"></div>
                    </div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ number_format($stats[$lang]['translated']) }} / {{ number_format($stats[$lang]['total']) }} keys translated
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Action Bar: Import/Export/Validate -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Default Language Selector -->
                <div class="flex items-center gap-3 pr-4 border-r border-slate-200 dark:border-slate-700">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">Default Language:</label>
                    <select id="defaultLocaleSelect" 
                            class="px-3 py-1.5 text-sm border-none bg-slate-50 dark:bg-slate-900 rounded-lg focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-300">
                        @foreach($supportedLocales as $lang)
                            <option value="{{ $lang }}" {{ $defaultLocale === $lang ? 'selected' : '' }}>
                                {{ $locales[$lang] ?? strtoupper($lang) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Export Dropdown -->
                <div class="relative inline-block" id="exportDropdownWrapper">
                    <button type="button" class="export-toggle-btn btn-secondary text-sm">
                        <i class="bi bi-download mr-2"></i>
                        Export
                        <i class="bi bi-chevron-down ml-2 text-xs transition-transform"></i>
                    </button>
                    <div class="export-dropdown-menu hidden absolute left-0 z-50 mt-2 w-48 rounded-xl bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">
                        <a href="{{ route('settings.language.export', ['format' => 'json']) }}" 
                           class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <i class="bi bi-filetype-json"></i> Export as JSON
                        </a>
                        <a href="{{ route('settings.language.export', ['format' => 'csv']) }}" 
                           class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <i class="bi bi-filetype-csv"></i> Export as CSV
                        </a>
                        @foreach($supportedLocales as $lang)
                            <a href="{{ route('settings.language.export', ['format' => 'json', 'language' => $lang]) }}" 
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                                <i class="bi bi-file-earmark-arrow-down"></i> {{ $locales[$lang] ?? $lang }} only
                            </a>
                        @endforeach
                    </div>
                </div>
                <script>
                (function() {
                    var wrapper = document.getElementById('exportDropdownWrapper');
                    var btn = wrapper.querySelector('.export-toggle-btn');
                    var menu = wrapper.querySelector('.export-dropdown-menu');
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        menu.classList.toggle('hidden');
                    });
                    document.addEventListener('click', function(e) {
                        if (!wrapper.contains(e.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                })();
                </script>

                <!-- Import Button -->
                <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" class="btn-secondary text-sm">
                    <i class="bi bi-upload mr-2"></i>
                    Import
                </button>

                <!-- Validate Button -->
                <button type="button" onclick="validateTranslations()" class="btn-secondary text-sm">
                    <i class="bi bi-shield-check mr-2"></i>
                    Validate
                </button>

                <!-- Sync from Files (if applicable) -->
                <form action="{{ route('settings.language.sync-from-files') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="language_code" value="en">
                    <button type="submit" class="btn-secondary text-sm" title="Import translations from lang/*.php files">
                        <i class="bi bi-arrow-repeat mr-2"></i>
                        Sync from Files
                    </button>
                </form>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
            <form method="GET" action="{{ route('settings.language') }}" class="flex flex-col lg:flex-row gap-4">
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

                <!-- Namespace Filter -->
                <div class="w-full lg:w-48">
                    <select name="namespace" 
                            class="block w-full px-4 py-2.5 border-none bg-slate-50 dark:bg-slate-900 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-300 appearance-none cursor-pointer">
                        <option value="">All Namespaces</option>
                        @foreach($namespaces as $ns)
                            <option value="{{ $ns }}" {{ $namespaceFilter === $ns ? 'selected' : '' }}>{{ $ns }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full lg:w-48">
                    <select name="status" 
                            class="block w-full px-4 py-2.5 border-none bg-slate-50 dark:bg-slate-900 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-300 appearance-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="complete" {{ $statusFilter === 'complete' ? 'selected' : '' }}>Complete</option>
                        <option value="incomplete" {{ $statusFilter === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                        <option value="empty" {{ $statusFilter === 'empty' ? 'selected' : '' }}>Empty</option>
                    </select>
                </div>

                <!-- Per Page Selector -->
                <div class="w-full lg:w-36">
                    <select name="per_page" 
                            class="block w-full px-4 py-2.5 border-none bg-slate-50 dark:bg-slate-900 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-300 appearance-none cursor-pointer">
                        @foreach($allowedPerPage as $pp)
                            <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }} per page</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-secondary">
                    <i class="bi bi-funnel mr-2"></i>Filter
                </button>
                
                @if($search || $statusFilter || $namespaceFilter)
                    <a href="{{ route('settings.language') }}" class="btn-secondary text-red-600 dark:text-red-400 hover:text-red-700">
                        <i class="bi bi-x-lg mr-2"></i>Clear
                    </a>
                @endif
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
                                    @php $meta = $localesWithMeta[$lang] ?? []; @endphp
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-1/4">
                                        <span class="flex items-center gap-2">
                                            {{ $locales[$lang] ?? $lang }}
                                            @if($meta['rtl'] ?? false)
                                                <span class="text-[9px] px-1 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">RTL</span>
                                            @endif
                                        </span>
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
                                    $namespace = explode('.', $key)[0] ?? '';
                                @endphp
                                <tr class="group transition-colors {{ $statusClass }}">
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex flex-col gap-1">
                                            <code class="text-xs font-mono text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded w-fit select-all break-all">{{ $key }}</code>
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <span class="text-[10px] text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $namespace }}</span>
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
                                        @php 
                                            $meta = $localesWithMeta[$lang] ?? [];
                                            $isRtl = $meta['rtl'] ?? false;
                                        @endphp
                                        <td class="px-6 py-4 align-top">
                                            <textarea name="translations[{{ $key }}][{{ $lang }}]" 
                                                      rows="2"
                                                      dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                                                      class="block w-full px-3 py-2 text-sm border-none bg-transparent focus:bg-white dark:focus:bg-slate-900 rounded-lg focus:ring-2 focus:ring-blue-500/20 transition-all resize-none placeholder-slate-300 dark:placeholder-slate-600 {{ $isRtl ? 'text-right' : '' }}"
                                                      placeholder="Enter translation...">{{ $langs[$lang]['value'] ?? '' }}</textarea>
                                            @if(!empty($langs[$lang]['updated_at']))
                                                <span class="text-[9px] text-slate-400 pl-1">Updated: {{ \Carbon\Carbon::parse($langs[$lang]['updated_at'])->diffForHumans() }}</span>
                                            @endif
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
                                    @php 
                                        $meta = $localesWithMeta[$lang] ?? [];
                                        $isRtl = $meta['rtl'] ?? false;
                                    @endphp
                                    <td class="px-6 py-4 align-top">
                                        <textarea name="new_translation[{{ $lang }}]" 
                                                  rows="2"
                                                  dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                                                  class="block w-full px-3 py-2 text-sm border-none bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500/20 resize-none placeholder-slate-400 {{ $isRtl ? 'text-right' : '' }}"
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
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        Showing {{ $translations->firstItem() ?? 0 }} to {{ $translations->lastItem() ?? 0 }} of {{ number_format($totalCount) }} keys
                        @if($perPage)
                            <span class="text-slate-400">({{ $perPage }} per page)</span>
                        @endif
                    </div>
                    
                    <!-- Custom Tailwind Pagination -->
                    @if($translations->hasPages())
                        <nav class="flex items-center gap-1">
                            {{-- Previous Page Link --}}
                            @if($translations->onFirstPage())
                                <span class="px-3 py-1.5 text-sm text-slate-400 dark:text-slate-600 cursor-not-allowed">
                                    <i class="bi bi-chevron-left"></i>
                                </span>
                            @else
                                <a href="{{ $translations->previousPageUrl() }}" 
                                   class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            @endif

                            {{-- Page Numbers --}}
                            @php
                                $currentPage = $translations->currentPage();
                                $lastPage = $translations->lastPage();
                                $start = max(1, $currentPage - 2);
                                $end = min($lastPage, $currentPage + 2);
                            @endphp

                            @if($start > 1)
                                <a href="{{ $translations->url(1) }}" 
                                   class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">1</a>
                                @if($start > 2)
                                    <span class="px-2 text-slate-400">...</span>
                                @endif
                            @endif

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $currentPage)
                                    <span class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg">{{ $page }}</span>
                                @else
                                    <a href="{{ $translations->url($page) }}" 
                                       class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($end < $lastPage)
                                @if($end < $lastPage - 1)
                                    <span class="px-2 text-slate-400">...</span>
                                @endif
                                <a href="{{ $translations->url($lastPage) }}" 
                                   class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">{{ $lastPage }}</a>
                            @endif

                            {{-- Next Page Link --}}
                            @if($translations->hasMorePages())
                                <a href="{{ $translations->nextPageUrl() }}" 
                                   class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @else
                                <span class="px-3 py-1.5 text-sm text-slate-400 dark:text-slate-600 cursor-not-allowed">
                                    <i class="bi bi-chevron-right"></i>
                                </span>
                            @endif
                        </nav>
                    @endif
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
                    <li>RTL languages are marked and text direction is handled automatically</li>
                    <li>Use Import/Export for bulk operations or translation teams</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-500/75 dark:bg-slate-900/80 transition-opacity" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white" id="modal-title">Import Translations</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Upload a JSON or CSV file with translations.</p>
                        
                        <div class="mt-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">File (JSON or CSV)</label>
                                <input type="file" name="file" accept=".json,.csv,.txt" required
                                       class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400">
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="overwrite" id="overwriteCheck" value="1"
                                       class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500">
                                <label for="overwriteCheck" class="text-sm text-slate-700 dark:text-slate-300">
                                    Overwrite existing translations
                                </label>
                            </div>
                            
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm text-amber-800 dark:text-amber-200">
                                <i class="bi bi-info-circle mr-2"></i>
                                JSON format: <code class="text-xs bg-amber-100 dark:bg-amber-900/40 px-1 rounded">{"key": {"en": "value", "fr": "valeur"}}</code>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-upload mr-2"></i>
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Validation Results Modal -->
    <div id="validationModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="validation-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-500/75 dark:bg-slate-900/80 transition-opacity" onclick="document.getElementById('validationModal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full max-h-[80vh] overflow-y-auto">
                <div class="px-6 py-5">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white" id="validation-title">Translation Validation Results</h3>
                    <div id="validationContent" class="mt-4">
                        <!-- Content will be injected here -->
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                    <button type="button" onclick="document.getElementById('validationModal').classList.add('hidden')" class="btn-secondary">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set default locale
        document.getElementById('defaultLocaleSelect')?.addEventListener('change', function() {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('locale', this.value);

            fetch('{{ route('settings.language.default-locale') }}', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Default language updated', 'success');
                } else {
                    showToast(data.message || 'Failed to update', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to update default language', 'error');
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

        // Import form handler
        document.getElementById('importForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('{{ route('settings.language.import') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('importModal').classList.add('hidden');
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Import failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Import failed', 'error');
            });
        });

        // Validate translations
        function validateTranslations() {
            const content = document.getElementById('validationContent');
            content.innerHTML = '<div class="text-center py-8"><i class="bi bi-arrow-repeat animate-spin text-2xl text-blue-500"></i><p class="mt-2 text-sm text-slate-500">Validating...</p></div>';
            document.getElementById('validationModal').classList.remove('hidden');
            
            fetch('{{ route('settings.language.validate') }}', {
                headers: { 'Accept': 'application/json' },
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    content.innerHTML = `<div class="text-red-600">${data.message}</div>`;
                    return;
                }
                
                let html = `
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">${data.summary.total_keys}</div>
                            <div class="text-xs text-slate-500">Total Keys</div>
                        </div>
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-amber-600">${data.summary.keys_with_issues}</div>
                            <div class="text-xs text-slate-500">With Issues</div>
                        </div>
                        <div class="bg-red-100 dark:bg-red-900/30 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-red-600">${data.summary.errors}</div>
                            <div class="text-xs text-red-600">Errors</div>
                        </div>
                        <div class="bg-amber-100 dark:bg-amber-900/30 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-amber-600">${data.summary.warnings}</div>
                            <div class="text-xs text-amber-600">Warnings</div>
                        </div>
                    </div>
                `;
                
                if (data.summary.keys_with_issues === 0) {
                    html += '<div class="text-center py-8 text-green-600"><i class="bi bi-check-circle-fill text-4xl"></i><p class="mt-2 font-medium">All translations are valid!</p></div>';
                } else {
                    html += '<div class="space-y-4 max-h-96 overflow-y-auto">';
                    for (const [key, issues] of Object.entries(data.issues)) {
                        html += `<div class="border border-slate-200 dark:border-slate-700 rounded-lg p-3">
                            <code class="text-xs font-mono text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">${key}</code>
                            <ul class="mt-2 space-y-1">`;
                        for (const issue of issues) {
                            const colorClass = issue.severity === 'error' ? 'text-red-600' : (issue.severity === 'warning' ? 'text-amber-600' : 'text-blue-600');
                            const iconClass = issue.severity === 'error' ? 'bi-x-circle-fill' : (issue.severity === 'warning' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill');
                            html += `<li class="text-xs ${colorClass}"><i class="bi ${iconClass} mr-1"></i>${issue.message}</li>`;
                        }
                        html += '</ul></div>';
                    }
                    html += '</div>';
                }
                
                content.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = '<div class="text-red-600">Validation failed</div>';
            });
        }

        // Toast notification helper
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-all transform ${
                type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
@endsection
