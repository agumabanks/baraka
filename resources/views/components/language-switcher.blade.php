@props([
    'style' => 'dropdown', // dropdown, inline, minimal, compact
    'showFlags' => true,
    'showLabels' => true,
    'persistPreference' => true, // Save preference to user settings
])

@php
    // Full locale metadata with RTL support and flags
    $allLocales = [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => '🇬🇧', 'rtl' => false],
        'fr' => ['label' => 'French', 'native' => 'Français', 'flag' => '🇫🇷', 'rtl' => false],
        'sw' => ['label' => 'Swahili', 'native' => 'Kiswahili', 'flag' => '🇰🇪', 'rtl' => false],
        'ar' => ['label' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true],
        'zh' => ['label' => 'Chinese', 'native' => '中文', 'flag' => '🇨🇳', 'rtl' => false],
        'es' => ['label' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸', 'rtl' => false],
        'de' => ['label' => 'German', 'native' => 'Deutsch', 'flag' => '🇩🇪', 'rtl' => false],
        'pt' => ['label' => 'Portuguese', 'native' => 'Português', 'flag' => '🇵🇹', 'rtl' => false],
        'hi' => ['label' => 'Hindi', 'native' => 'हिन्दी', 'flag' => '🇮🇳', 'rtl' => false],
        'ja' => ['label' => 'Japanese', 'native' => '日本語', 'flag' => '🇯🇵', 'rtl' => false],
        'ko' => ['label' => 'Korean', 'native' => '한국어', 'flag' => '🇰🇷', 'rtl' => false],
        'ru' => ['label' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺', 'rtl' => false],
    ];
    
    // Get supported locales from config
    $supportedCodes = config('translations.supported', ['en', 'fr', 'sw']);
    $locales = collect($allLocales)->only($supportedCodes)->toArray();
    
    $currentLocale = app()->getLocale();
    if (!isset($locales[$currentLocale])) {
        $currentLocale = 'en'; // Fallback
    }
    
    $currentUrl = request()->fullUrl();
    
    // Build URL with lang parameter (clean implementation)
    $buildUrl = function($lang) use ($currentUrl) {
        $url = preg_replace('/([?&])lang=[^&]*(&|$)/', '$1', $currentUrl);
        $url = rtrim($url, '?&');
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'lang=' . $lang;
    };
@endphp

@if($style === 'dropdown')
<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open" 
            type="button"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        @if($showFlags)
            <span class="text-base">{{ $locales[$currentLocale]['flag'] }}</span>
        @endif
        @if($showLabels)
            <span class="hidden sm:inline">{{ $locales[$currentLocale]['native'] }}</span>
        @endif
        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-xl bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 focus:outline-none overflow-hidden"
         style="display: none;">
        <div class="py-1">
            @foreach($locales as $code => $locale)
                <a href="{{ $buildUrl($code) }}" 
                   class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors {{ $code === $currentLocale ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                    @if($showFlags)
                        <span class="text-lg">{{ $locale['flag'] }}</span>
                    @endif
                    <div class="flex flex-col">
                        <span class="font-medium">{{ $locale['native'] }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $locale['label'] }}</span>
                    </div>
                    @if($code === $currentLocale)
                        <svg class="w-4 h-4 ml-auto text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>

@elseif($style === 'inline')
<div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg">
    @foreach($locales as $code => $locale)
        <a href="{{ $buildUrl($code) }}" 
           class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $code === $currentLocale ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            @if($showFlags)
                <span>{{ $locale['flag'] }}</span>
            @endif
            @if($showLabels)
                <span class="hidden sm:inline">{{ strtoupper($code) }}</span>
            @endif
        </a>
    @endforeach
</div>

@elseif($style === 'compact')
{{-- Compact style: just current locale code with dropdown --}}
<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open" 
            type="button"
            class="flex items-center gap-1 px-2 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700 rounded transition-all focus:outline-none">
        {{ strtoupper($currentLocale) }}
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="open" 
         x-transition
         class="absolute right-0 z-50 mt-1 w-36 origin-top-right rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 overflow-hidden"
         style="display: none;">
        @foreach($locales as $code => $locale)
            <a href="{{ $buildUrl($code) }}" 
               class="flex items-center gap-2 px-3 py-2 text-xs transition-colors {{ $code === $currentLocale ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                <span>{{ $locale['flag'] }}</span>
                <span>{{ $locale['native'] }}</span>
            </a>
        @endforeach
    </div>
</div>

@else {{-- minimal --}}
<div class="flex items-center gap-2">
    @foreach($locales as $code => $locale)
        <a href="{{ $buildUrl($code) }}" 
           title="{{ $locale['native'] }} ({{ $locale['label'] }})"
           class="w-8 h-8 flex items-center justify-center rounded-full text-sm transition-all {{ $code === $currentLocale ? 'bg-blue-100 dark:bg-blue-900/30 ring-2 ring-blue-500' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}"
           @if($locale['rtl'] ?? false) dir="rtl" @endif>
            {{ $locale['flag'] }}
        </a>
    @endforeach
</div>
@endif
