<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-run migrations in development
        if (app()->environment('local', 'development')) {
            try {
                \Artisan::call('migrate', ['--force' => true]);
            } catch (\Exception $e) {
                // Silently fail if migrations can't run
            }
        }
    }
}
