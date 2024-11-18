<?php

use Illuminate\Support\Facades\Route;
use JobMetric\Language\Http\Controllers\BaseLanguageController;
use JobMetric\Panelio\Facades\Middleware;

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
    Route::middleware(Middleware::getMiddlewares())->group(function () {
        Route::post('set', [BaseLanguageController::class, 'setLanguage'])->name('set');
    });
});
