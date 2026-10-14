<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\ScribeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(ScribeServiceProvider::class)) {
            $this->app->register(LocalApiDocumentationServiceProvider::class);
        }

        foreach (config('modules.providers', []) as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        foreach (config('modules.routes') as $key => $portal) {
            foreach ($portal as $route) {

                $version = $route['version'] ?? 'v1';
                $prefix = $route['prefix'] ?? '';
                $middlewares = array_merge(['api', 'locale'], $route['middlewares'] ?? []);

                if ($route['api_key'] ?? true) {
                    $middlewares[] = 'api.key';
                }

                Route::middleware($middlewares)
                    ->prefix("api/$version/$prefix")
                    ->group(base_path("routes/$version/$key/{$route['file']}"));
            }
        }
    }
}
