<?php

namespace App\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Providers\PaymentProviders\MercadoPagoPaymentProvider;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProviderInterface::class, function ($app) {
            // Get provider from config
            $provider = config('services.payment.provider', 'mercadopago');

            return match ($provider) {
                'mercadopago' => new MercadoPagoPaymentProvider(),
                default => new MercadoPagoPaymentProvider(),
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
