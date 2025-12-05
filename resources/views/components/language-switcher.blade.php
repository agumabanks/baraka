@props([
    'style' => 'dropdown', // dropdown, inline, minimal
    'showFlags' => true,
    'showLabels' => true,
])

@php
    $allLocales = [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
        'fr' => ['label' => 'French', 'native' => 'Français', 'flag' => '🇫🇷'],
        'sw' => ['label' => 'Swahili', 'native' => 'Kiswahili', 'flag' => '🇰🇪'],
    ];
    
    $supportedCodes = config('translations.supported', ['en', 'fr', 'sw']);
    $locales = collect($allLocales)->only($supportedCodes)->toArray();
    
    $currentLocale = app()->getLocale();
    if (!isset($locales[$currentLocale])) {
        $currentLocale = 'en';
    }
    
    $currentUrl = request()->fullUrl();
    
    $buildUrl = function($lang) use ($currentUrl) {
        $url = preg_replace('/([?&])lang=[^&]*(&|$)/', '$1', $currentUrl);
        $url = rtrim($url, '?&');
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'lang=' . $lang;
    };
@endphp

@if($style === 'dropdown')
<div class="relative inline-block">
    <button type="button" class="lang-toggle-btn flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        @if($showFlags)
            <span class="text-base">{{ $locales[$currentLocale]['flag'] }}</span>
        @endif
        @if($showLabels)
            <span class="hidden sm:inline">{{ $locales[$currentLocale]['native'] }}</span>
        @endif
        <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div class="lang-dropdown-menu hidden absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-xl bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">
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

<script>
(function() {
    const wrapper = document.currentScript.previousElementSibling;
    const btn = wrapper.querySelector('.lang-toggle-btn');
    const menu = wrapper.querySelector('.lang-dropdown-menu');
    
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    }
})();
</script>

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

@else {{-- minimal --}}
<div class="flex items-center gap-2">
    @foreach($locales as $code => $locale)
        <a href="{{ $buildUrl($code) }}" 
           title="{{ $locale['native'] }} ({{ $locale['label'] }})"
           class="w-8 h-8 flex items-center justify-center rounded-full text-sm transition-all {{ $code === $currentLocale ? 'bg-blue-100 dark:bg-blue-900/30 ring-2 ring-blue-500' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
            {{ $locale['flag'] }}
        </a>
    @endforeach
</div>
@endif
