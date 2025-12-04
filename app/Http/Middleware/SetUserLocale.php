<?php

namespace App\Http\Middleware;

use App\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    protected array $supportedLocales = ['en', 'fr', 'sw'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        // 1. Check query param for locale switching
        if ($request->has('lang') && $this->isSupported($request->query('lang'))) {
            $locale = $request->query('lang');
            
            // Persist to user settings if authenticated
            if ($user = $request->user()) {
                UserSetting::setLocale($user->id, $locale);
            }
            
            Session::put('locale', $locale);
            return $locale;
        }

        // 2. Check session (for guests who switched language)
        if ($sessionLocale = Session::get('locale')) {
            if ($this->isSupported($sessionLocale)) {
                return $sessionLocale;
            }
        }

        // 3. Check authenticated user's preference
        if ($user = $request->user()) {
            $userLocale = UserSetting::getLocale($user->id);
            if ($this->isSupported($userLocale)) {
                return $userLocale;
            }
        }

        // 4. Check browser Accept-Language header
        $browserLocale = $request->getPreferredLanguage($this->supportedLocales);
        if ($browserLocale && $this->isSupported($browserLocale)) {
            return $browserLocale;
        }

        // 5. Fall back to system default
        return config('app.locale', 'en');
    }

    protected function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales);
    }
}
