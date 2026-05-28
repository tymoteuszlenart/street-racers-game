<?php

namespace App\DTOs;

use App\Models\PaymentOrder;

final readonly class CheckoutSessionResult
{
    public function __construct(
        public PaymentOrder $order,
        public string $checkoutUrl,
    ) {}
}
