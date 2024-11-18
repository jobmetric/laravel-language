<?php

use Illuminate\Support\Facades\Route;
use JobMetric\Language\Http\Controllers\BaseLanguageController;
use JobMetric\Language\Http\Controllers\LanguageController;
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

// route language in panel
Route::prefix('p/{panel}/{section}')->group(function () {
    Route::middleware(Middleware::getMiddlewares())->name('language.')->group(function(){
        Route::options('language', [LanguageController::class, 'options'])->name('options');
        Route::resource('language', LanguageController::class)->except(['show', 'destroy']);
    });
});
