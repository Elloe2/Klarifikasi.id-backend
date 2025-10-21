<?php

namespace App\Providers;

use App\Services\GoogleSearchService;
use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services untuk dependency injection
        $this->app->singleton(GoogleSearchService::class);
        $this->app->singleton(GeminiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
