<?php

namespace App\Exceptions;

use Exception;

class IdempotencyKeyConflictException extends Exception
{
    public function __construct()
    {
        parent::__construct('This idempotency key was already used for a different race request.');
    }
}
