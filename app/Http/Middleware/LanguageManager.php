<?php

namespace App\Http\Middleware;

use App\Models\UserSetting;
use App\Support\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LanguageManager
{
    /**
     * Handle an incoming request.
     * Resolves locale in order:
     * 1. Query param (?lang=xx) - for switching
     * 2. Session (for guests who switched)
     * 3. UserSetting (DB-stored preference)
     * 4. User model preferred_language (legacy)
     * 5. Browser Accept-Language header
     * 6. System default
     */
    public function handle(Request $request, Closure $next)
    {
        $allowed = translation_supported_languages();
        $mode = SystemSettings::localizationMode();
        $locale = $this->resolveLocale($request, $allowed, $mode);

        App::setLocale($locale);
        session()->put('locale', $locale);

        if (
            $mode !== 'global'
            && $this->isAllowed($locale, $allowed)
            && $request->cookie('locale') !== $locale
        ) {
            // Persist for guests + convenience for authenticated users (Apple-like "stickiness")
            Cookie::queue('locale', $locale, 60 * 24 * 365); // 1 year
        }

        return $next($request);
    }

    protected function resolveLocale(Request $request, array $allowed, string $mode): string
    {
        $default = SystemSettings::defaultLocale();
        $user = $request->user();
        $customer = Auth::guard('customer')->user();

        if ($mode === 'global') {
            return $this->isAllowed($default, $allowed) ? $default : 'en';
        }

        // 1. Check query param for explicit switching
        if ($request->has('lang')) {
            $queryLocale = $request->query('lang');
            if ($this->isAllowed($queryLocale, $allowed)) {
                // Persist to user settings if authenticated
                if ($user) {
                    UserSetting::setLocale($user->id, $queryLocale);
                    $user->preferred_language = $queryLocale;
                    $user->save();
                } elseif ($customer && isset($customer->preferred_language)) {
                    $customer->preferred_language = $queryLocale;
                    $customer->save();
                }
                return $queryLocale;
            }
        }

        // 2. Check authenticated preferences
        if ($user) {
            try {
                $userSettingLocale = UserSetting::getLocale($user->id);
                if ($this->isAllowed($userSettingLocale, $allowed)) {
                    return $userSettingLocale;
                }
            } catch (\Throwable $e) {
                // Table may not exist yet during migrations
            }

            // Legacy: Check user's preferred_language column
            if (!empty($user->preferred_language) && $this->isAllowed($user->preferred_language, $allowed)) {
                return $user->preferred_language;
            }
        }

        if ($customer && !empty($customer->preferred_language) && $this->isAllowed($customer->preferred_language, $allowed)) {
            return $customer->preferred_language;
        }

        // 3. Check cookie (guests / cross-session)
        $cookieLocale = $request->cookie('locale');
        if ($this->isAllowed($cookieLocale, $allowed)) {
            return $cookieLocale;
        }

        // 4. Check session (guests who previously switched)
        if (session()->has('locale')) {
            $sessionLocale = session()->get('locale');
            if ($this->isAllowed($sessionLocale, $allowed)) {
                return $sessionLocale;
            }
        }

        // 5. Check browser Accept-Language header
        $browserLocale = $request->getPreferredLanguage($allowed);
        if ($browserLocale && $this->isAllowed($browserLocale, $allowed)) {
            return $browserLocale;
        }

        // 6. Fall back to system default
        return $this->isAllowed($default, $allowed) ? $default : 'en';
    }

    protected function isAllowed(mixed $locale, array $allowed): bool
    {
        return is_string($locale) && in_array($locale, $allowed, true);
    }
}
