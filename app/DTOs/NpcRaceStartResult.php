<?php

namespace App\DTOs;

use App\Models\RaceAttempt;
use App\Models\RaceResult;

readonly class NpcRaceStartResult
{
    public function __construct(
        public RaceResult $raceResult,
        public RaceAttempt $raceAttempt,
        public bool $replayed,
    ) {}
}
