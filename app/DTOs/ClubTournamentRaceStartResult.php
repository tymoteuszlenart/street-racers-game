<?php

namespace App\DTOs;

use App\Models\ClubTournamentEntry;
use App\Models\RaceAttempt;
use App\Models\RaceResult;

class ClubTournamentRaceStartResult
{
    public function __construct(
        public readonly RaceResult $raceResult,
        public readonly RaceAttempt $raceAttempt,
        public readonly ClubTournamentEntry $entry,
        public readonly bool $replayed,
    ) {}
}
