<?php

namespace App\Providers;

use App\Contracts\NfProviderInterface;
use App\Providers\NfProviders\FocusNfeProvider;
use Illuminate\Support\ServiceProvider;

class NfServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(NfProviderInterface::class, function ($app) {
            // Get provider from config
            $provider = config('services.nf.provider', 'focus');

            return match ($provider) {
                'focus' => new FocusNfeProvider(),
                default => new FocusNfeProvider(),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
