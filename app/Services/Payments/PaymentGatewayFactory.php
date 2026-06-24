<?php

namespace App\Services\Payments;

class PaymentGatewayFactory
{
    /**
     * Create a payment gateway instance based on the provider name.
     *
     * @param  string  $provider  The gateway provider (e.g. 'stripe', 'paypal').
     * @return PaymentGatewayInterface
     *
     * @throws \InvalidArgumentException  If the provider is not supported.
     */
    public function make(string $provider): PaymentGatewayInterface
    {
        return match (strtolower($provider)) {
            'stripe' => app('payment.gateway.stripe'),
            'paypal' => app('payment.gateway.paypal'),
            default  => throw new \InvalidArgumentException("Unsupported payment provider: {$provider}"),
        };
    }
}
