<?php

namespace App\Http\Middleware;

use App\Support\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class BranchLocale
{
    public function handle(Request $request, Closure $next)
    {
        $branch = $request->attributes->get('branch');
        // Start with current locale (set by LanguageManager or user preference)
        $locale = App::getLocale();
        $supported = translation_supported_languages();

        if ($branch && isset($branch->metadata['settings']['preferred_language'])) {
            $candidate = $branch->metadata['settings']['preferred_language'];
            if (in_array($candidate, $supported, true)) {
                $locale = $candidate;
            }
        }
        
        if ($locale !== App::getLocale()) {
            App::setLocale($locale);
            session()->put('locale', $locale);
        }

        return $next($request);
    }
}
