<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DatabaseTranslations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Preload translations for the current locale
        $locale = app()->getLocale();
        
        try {
            $translations = \App\Models\Translation::getAllForLanguage($locale);
            
            // Store in Laravel's translation loader
            $translator = app('translator');
            $translator->setLoaded([$namespace.'::'.$group => $translations]);
        } catch (\Exception $e) {
            // If database is not available, continue silently
            // This allows the system to work during installation/migration
        }

        return $next($request);
    }
}
