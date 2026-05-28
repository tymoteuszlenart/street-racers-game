<?php

namespace App\DTOs;

use App\Models\RaceAttempt;

readonly class ResolvedRaceAttempt
{
    public function __construct(
        public RaceAttempt $attempt,
        public bool $isNew,
    ) {}
}
