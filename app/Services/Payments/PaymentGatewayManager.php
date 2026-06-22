<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve a gateway driver by key.
     */
    public function gateway(?string $key = null): PaymentGateway
    {
        $key ??= (string) config('payments.default', 'payu');
        $config = config("payments.gateways.{$key}");

        if (! $config || empty($config['driver'])) {
            throw new InvalidArgumentException("Unknown payment gateway [{$key}].");
        }

        return app($config['driver']);
    }

    /**
     * Whether a gateway is configured and enabled.
     */
    public function isEnabled(string $key): bool
    {
        return (bool) config("payments.gateways.{$key}.enabled", false);
    }

    /**
     * All enabled gateways keyed by gateway key.
     *
     * @return array<string, PaymentGateway>
     */
    public function enabled(): array
    {
        $out = [];

        foreach (array_keys((array) config('payments.gateways', [])) as $key) {
            if ($this->isEnabled($key)) {
                $out[$key] = $this->gateway($key);
            }
        }

        return $out;
    }
}
