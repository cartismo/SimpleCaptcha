<?php

use Illuminate\Support\Facades\Route;
use Modules\SimpleCaptcha\Http\Controllers\Api\CaptchaController;

/*
|--------------------------------------------------------------------------
| SimpleCaptcha API Routes
|--------------------------------------------------------------------------
|
| API routes for captcha generation and verification.
|
*/

Route::get('/generate', [CaptchaController::class, 'generate'])->name('captcha.generate');
Route::post('/verify', [CaptchaController::class, 'verify'])->name('captcha.verify');