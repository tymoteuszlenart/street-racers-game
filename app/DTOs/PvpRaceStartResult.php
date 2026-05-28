<?php

namespace App\DTOs;

use App\Models\PvpRace;
use App\Models\RaceAttempt;
use App\Models\RaceResult;

readonly class PvpRaceStartResult
{
    public function __construct(
        public RaceResult $raceResult,
        public PvpRace $pvpRace,
        public RaceAttempt $raceAttempt,
        public bool $replayed,
    ) {}
}
