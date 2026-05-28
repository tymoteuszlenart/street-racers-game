<?php

namespace App\Exceptions;

use Exception;

class RaceAttemptFailedException extends Exception
{
    public function __construct(
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct('This idempotency key was used by a failed race attempt. Use a new key to retry.');
    }
}
