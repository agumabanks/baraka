<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class FrontendTranslations
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Share all translations with all views
        $locale = app()->getLocale();
        $supported = translation_supported_languages();

        try {
            $translations = get_translation_cache($locale);
        } catch (\Throwable $e) {
            $translations = [];
        }

        View::share('dbTranslations', $translations);
        View::share('currentLanguage', $locale);
        View::share('supportedLanguages', $supported);

        return $next($request);
    }
}
