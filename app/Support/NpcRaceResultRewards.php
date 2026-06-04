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
        $storedExperience = $raceResult->score_breakdown['rewards']['experience'] ?? null;

        if ($storedExperience !== null) {
            $experience = (int) $storedExperience;
        } else {
            $profile = $raceResult->user?->playerProfile;
            $atMaxLevel = $profile !== null && $profile->level >= (int) config('game.player.max_level', 100);
            $experience = $atMaxLevel
                ? 0
                : ($won ? $race->experience_reward_win : $race->experience_reward_loss);
        }

        return [
            'cash' => $won ? $race->cash_reward_win : $race->cash_reward_loss,
            'reputation' => $won ? $race->reputation_reward_win : $race->reputation_reward_loss,
            'experience' => $experience,
        ];
    }
}
