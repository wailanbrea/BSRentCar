<?php

namespace App\Providers;

use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\StripePaymentGateway;
use Illuminate\Support\ServiceProvider;

use App\Services\Payments\PayPalPaymentGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Named and class bindings for the gateways (Fase 7)
        $this->app->singleton(StripePaymentGateway::class, function ($app) {
            return new StripePaymentGateway(
                config('rentcar.stripe.secret_key', ''),
            );
        });
        $this->app->bind('payment.gateway.stripe', function ($app) {
            return $app->make(StripePaymentGateway::class);
        });

        $this->app->singleton(PayPalPaymentGateway::class, function ($app) {
            return new PayPalPaymentGateway(
                clientId: config('rentcar.paypal.client_id', ''),
                clientSecret: config('rentcar.paypal.client_secret', ''),
                sandbox: config('rentcar.paypal.sandbox', true),
            );
        });
        $this->app->bind('payment.gateway.paypal', function ($app) {
            return $app->make(PayPalPaymentGateway::class);
        });

        $this->app->singleton(\App\Services\Payments\WalletPaymentGateway::class, function ($app) {
            return new \App\Services\Payments\WalletPaymentGateway(
                $app->make(\App\Services\WalletService::class)
            );
        });
        $this->app->bind('payment.gateway.wallet', function ($app) {
            return $app->make(\App\Services\Payments\WalletPaymentGateway::class);
        });

        // Fallback default interface binding to Stripe gateway
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return $app->make(StripePaymentGateway::class);
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
