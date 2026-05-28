<?php

namespace App\Exceptions;

use Exception;

class RaceStartRateLimitedException extends Exception
{
    public function __construct(
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Too many race start attempts. Please wait and try again.');
    }
}
