<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JobMetric\Language\Http\Controllers\LanguageController;

/*
|--------------------------------------------------------------------------
| Laravel Language Routes
|--------------------------------------------------------------------------
|
| All Route in Laravel Language package
|
*/

// language
Route::prefix('language')->name('language.')->namespace('JobMetric\language\Http\Controllers')->group(function () {
    Route::middleware([
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        SubstituteBindings::class
    ])->group(function () {
        Route::post('set', [LanguageController::class, 'setLanguage'])->name('set');
    });
});
