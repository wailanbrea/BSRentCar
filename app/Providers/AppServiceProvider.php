<?php

namespace App\Providers;

use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\StripePaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Gateway de pago por defecto: Stripe (Fase 6).
        // En Fase 7 (PayPal) se cambiará a un factory/resolver por proveedor.
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return new StripePaymentGateway(
                config('rentcar.stripe.secret_key', ''),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
