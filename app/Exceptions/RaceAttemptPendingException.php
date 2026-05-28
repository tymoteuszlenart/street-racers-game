<?php

namespace App\Exceptions;

use Exception;

class RaceAttemptPendingException extends Exception
{
    public function __construct()
    {
        parent::__construct('A race with this idempotency key is already in progress.');
    }
}
