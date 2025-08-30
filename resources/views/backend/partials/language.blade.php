<button class="btn  dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    @if (app()->getLocale() == 'en')
    <i class="flag-icon flag-icon-us"></i> {{ __('levels.english') }}
    @elseif(app()->getLocale() == 'bn')
    <i class="flag-icon flag-icon-bd"></i> {{ __('levels.bangla') }}
    @elseif(app()->getLocale() == 'in')
    <i class="flag-icon flag-icon-in"></i> {{ __('levels.hindi') }}
    @elseif(app()->getLocale() == 'ar')
    <i class="flag-icon flag-icon-sa"></i> {{ __('levels.arabic') }}
    @elseif(app()->getLocale() == 'fr')
    <i class="flag-icon flag-icon-fr"></i> {{ __('levels.franch') }}
    @elseif(app()->getLocale() == 'es')
    <i class="flag-icon flag-icon-es"></i> {{ __('levels.spanish') }}
    @elseif(app()->getLocale() == 'zh')
    <i class="flag-icon flag-icon-cn"></i> {{ __('levels.chinese') }}
    @endif
</button>
<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
    <a class="dropdown-item" href="{{ route('setlocalization', 'en') }}"> <i class="flag-icon flag-icon-us"></i>
        {{ __('levels.english') }}</a>
    <a class="dropdown-item" href="{{ route('setlocalization', 'bn') }}"> <i class="flag-icon flag-icon-bd"></i>
        {{ __('levels.bangla') }}</a>
    <a class="dropdown-item" href="{{ route('setlocalization', 'in') }}"> <i class="flag-icon flag-icon-in"></i>
        {{ __('levels.hindi') }}</a>
    <a class="dropdown-item" href="{{ route('setlocalization', 'ar') }}"> <i class="flag-icon flag-icon-sa"></i>
        {{ __('levels.arabic') }}</a>
    <a class="dropdown-item" href="{{ route('setlocalization', 'fr') }}"> <i class="flag-icon flag-icon-fr"></i>
        {{ __('levels.franch') }}</a>
    <a class="dropdown-item" href="{{ route('setlocalization', 'es') }}"> <i class="flag-icon flag-icon-es"></i>
        {{ __('levels.spanish') }}</a>
    <a class="dropdown-item" href="{{ route('setlocalization', 'zh') }}"> <i class="flag-icon flag-icon-cn"></i>
        {{ __('levels.chinese') }}</a>
</div>