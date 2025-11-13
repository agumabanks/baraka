<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $allowed = translation_supported_languages();
        $default = config('app.locale', 'en');
        $locale = $default;

        $user = $request->user();
        if ($user && in_array($user->preferred_language, $allowed, true)) {
            $locale = $user->preferred_language;
            session()->put('locale', $locale);
        } elseif (session()->has('locale')) {
            $candidate = session()->get('locale');
            if (in_array($candidate, $allowed, true)) {
                $locale = $candidate;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
