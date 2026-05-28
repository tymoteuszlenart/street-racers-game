<?php

namespace App\Exceptions;

use Exception;

class IdempotencyKeyExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('The idempotency key has expired. Use a new key to start a race.');
    }
}
