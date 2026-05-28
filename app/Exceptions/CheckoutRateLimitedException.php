<?php

namespace App\Exceptions;

use Exception;

class CheckoutRateLimitedException extends Exception
{
    public function __construct(
        public readonly int $retryAfterSeconds,
        string $message = 'Too many checkout attempts. Please try again shortly.',
    ) {
        parent::__construct($message);
    }
}
