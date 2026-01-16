<?php

namespace Modules\SimpleCaptcha\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\SimpleCaptcha\Http\Controllers';

    /**
     * Called before routes are registered.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapAdminRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define the "admin" routes for the module.
     */
    protected function mapAdminRoutes(): void
    {
        Route::middleware([
            'web',
            'auth:sanctum',
            config('jetstream.auth_session'),
            'verified',
        ])
            ->prefix('admin')
            ->group(module_path('SimpleCaptcha', '/Routes/admin.php'));
    }

    /**
     * Define the "api" routes for the module (captcha generation/verification).
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware(['web'])
            ->prefix('api/captcha')
            ->group(module_path('SimpleCaptcha', '/Routes/api.php'));
    }
}