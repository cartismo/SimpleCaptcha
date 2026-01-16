<?php

use Illuminate\Support\Facades\Route;
use Modules\SimpleCaptcha\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| SimpleCaptcha Admin Routes
|--------------------------------------------------------------------------
|
| Admin routes for Simple CAPTCHA settings.
|
*/

Route::prefix('modules/captcha/simple-captcha')->name('admin.captcha.simple.')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});