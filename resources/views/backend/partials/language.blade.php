@php
    $languageDropdownId = $languageDropdownId ?? 'languageDropdown-' . uniqid();
    $currentLocale = app()->getLocale();
    $languages = [
        'en' => ['flag' => 'us', 'name' => __('levels.english')],
        'bn' => ['flag' => 'bd', 'name' => __('levels.bangla')],
        'in' => ['flag' => 'in', 'name' => __('levels.hindi')],
        'ar' => ['flag' => 'sa', 'name' => __('levels.arabic')],
        'fr' => ['flag' => 'fr', 'name' => __('levels.franch')],
        'es' => ['flag' => 'es', 'name' => __('levels.spanish')],
        'zh' => ['flag' => 'cn', 'name' => __('levels.chinese')]
    ];
    $currentLanguage = $languages[$currentLocale] ?? $languages['en'];
@endphp

<a class="nav-link dropdown-toggle" href="#" id="{{ $languageDropdownId }}" role="button"
   data-bs-toggle="dropdown" aria-expanded="false" aria-label="Select language">
    <i class="flag-icon flag-icon-{{ $currentLanguage['flag'] }}"></i>
    <span class="d-none d-md-inline">{{ $currentLanguage['name'] }}</span>
</a>
<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $languageDropdownId }}">
    @foreach($languages as $code => $lang)
        <li>
            <a class="dropdown-item {{ $currentLocale == $code ? 'active' : '' }}"
               href="{{ route('setlocalization', $code) }}">
                <i class="flag-icon flag-icon-{{ $lang['flag'] }}"></i> {{ $lang['name'] }}
            </a>
        </li>
    @endforeach
</ul>