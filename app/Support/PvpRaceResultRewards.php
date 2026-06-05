<?php

namespace App\Support;

use App\Enums\RaceAttemptType;
use App\Models\RaceResult;

class PvpRaceResultRewards
{
    /**
     * @return array{cash: int, reputation: int}|null
     */
    public static function forResult(RaceResult $raceResult): ?array
    {
        if ($raceResult->attempt_type !== RaceAttemptType::Pvp) {
            return null;
        }

        $stored = $raceResult->score_breakdown['rewards'] ?? null;

        if (! is_array($stored)) {
            return null;
        }

        return [
            'cash' => (int) ($stored['cash'] ?? 0),
            'reputation' => (int) ($stored['reputation'] ?? 0),
        ];
    }
}
