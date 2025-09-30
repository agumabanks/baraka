<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;

class LocalizationController extends Controller
{
    public function setLocalization($language)
    {
        App::setLocale($language);
        session()->put('locale', $language);

        return redirect()->back();
    }
}
