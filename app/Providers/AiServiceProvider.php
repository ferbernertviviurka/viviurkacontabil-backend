<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Models\Setting;
use App\Providers\AiProviders\ClarifAiProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AiProviderInterface::class, function ($app) {
            // Get provider from settings (database) or config, default to clarifai
            $provider = Setting::get('ai.provider', config('services.ai.provider', 'clarifai'));

            // Always use ClarifAI
            return new ClarifAiProvider();
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
