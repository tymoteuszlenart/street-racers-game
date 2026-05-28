<?php

namespace App\Support;

use App\Enums\RaceAttemptType;
use App\Models\RaceResult;

class NpcRaceResultRewards
{
    /**
     * @return array{cash: int, reputation: int, experience: int}|null
     */
    public static function forResult(RaceResult $raceResult): ?array
    {
        if ($raceResult->attempt_type !== RaceAttemptType::Npc) {
            return null;
        }

        $race = $raceResult->race;

        if ($race === null) {
            return null;
        }

        $won = $raceResult->won;

        return [
            'cash' => $won ? $race->cash_reward_win : $race->cash_reward_loss,
            'reputation' => $won ? $race->reputation_reward_win : $race->reputation_reward_loss,
            'experience' => $won ? $race->experience_reward_win : $race->experience_reward_loss,
        ];
    }
}
