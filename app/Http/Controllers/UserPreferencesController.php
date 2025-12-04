<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class UserPreferencesController extends Controller
{
    /**
     * Get current user's preferences
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'locale' => UserSetting::getLocale($user->id),
                'theme' => UserSetting::getTheme($user->id),
                'timezone' => UserSetting::getTimezone($user->id),
                'date_format' => UserSetting::getDateFormat($user->id),
                'all' => UserSetting::getAllForUser($user->id),
            ],
        ]);
    }

    /**
     * Update user locale/language
     */
    public function setLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:en,fr,sw',
        ]);

        $user = $request->user();
        $locale = $request->input('locale');

        UserSetting::setLocale($user->id, $locale);
        App::setLocale($locale);

        return response()->json([
            'success' => true,
            'message' => trans_db('settings.language.updated', [], $locale, 'Language updated successfully'),
            'locale' => $locale,
        ]);
    }

    /**
     * Update multiple preferences
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'sometimes|string|in:en,fr,sw',
            'theme' => 'sometimes|string|in:light,dark,auto',
            'timezone' => 'sometimes|string|timezone',
            'date_format' => 'sometimes|string|max:20',
        ]);

        $user = $request->user();
        $settings = $request->only(['locale', 'theme', 'timezone', 'date_format']);

        foreach ($settings as $key => $value) {
            if ($value !== null) {
                UserSetting::setValue($user->id, $key, $value);
            }
        }

        if (isset($settings['locale'])) {
            App::setLocale($settings['locale']);
        }

        return response()->json([
            'success' => true,
            'message' => trans_db('settings.preferences.updated', [], null, 'Preferences updated successfully'),
            'data' => UserSetting::getAllForUser($user->id),
        ]);
    }

    /**
     * Get available locales
     */
    public function locales(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'supported' => [
                    'en' => ['label' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
                    'fr' => ['label' => 'French', 'native' => 'Français', 'flag' => '🇫🇷'],
                    'sw' => ['label' => 'Swahili', 'native' => 'Kiswahili', 'flag' => '🇰🇪'],
                ],
                'current' => app()->getLocale(),
                'default' => config('app.locale', 'en'),
            ],
        ]);
    }
}
