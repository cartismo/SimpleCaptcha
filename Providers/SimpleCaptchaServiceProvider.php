<?php

namespace Modules\SimpleCaptcha\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SimpleCaptcha\Services\SimpleCaptchaService;

class SimpleCaptchaServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'SimpleCaptcha';

    protected string $moduleNameLower = 'simplecaptcha';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(SimpleCaptchaService::class, function ($app) {
            return new SimpleCaptchaService();
        });
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SimpleCaptchaService::class,
        ];
    }
}