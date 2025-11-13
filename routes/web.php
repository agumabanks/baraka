<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Entry Route
|--------------------------------------------------------------------------
|
| All browser routes are handled by the React single page application.
| Any request that is not an API call will be served the compiled
| React bundle located under public/app/index.html.
|
*/

Route::get('/{any?}', SpaController::class)
    ->where('any', '^(?!api).*')
    ->name('spa.entry');
