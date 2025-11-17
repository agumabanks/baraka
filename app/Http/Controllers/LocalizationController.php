<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;

class LocalizationController extends Controller
{
    public function setLocalization($language)
    {
        $allowed = config('translations.supported', ['en']);

        if (! in_array($language, $allowed, true)) {
            $language = config('app.locale', 'en');
        }

        App::setLocale($language);
        session()->put('locale', $language);

        return redirect()->back();
    }
}
