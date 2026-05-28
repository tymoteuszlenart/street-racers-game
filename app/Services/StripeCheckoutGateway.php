<?php

namespace App\Services;

use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeCheckoutGateway
{
    public function __construct(
        private readonly ?string $apiSecret = null,
    ) {}

    public function createCheckoutSession(array $params): Session
    {
        $secret = $this->apiSecret ?? config('services.stripe.secret');

        if ($secret === null || $secret === '') {
            throw new RuntimeException('Stripe secret is not configured.');
        }

        $client = new StripeClient($secret);

        return $client->checkout->sessions->create($params);
    }
}
