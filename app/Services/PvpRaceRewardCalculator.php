<?php

namespace App\Services;

class PvpRaceRewardCalculator
{
    /**
     * @return array{cash: int, reputation: int}
     */
    public function forOpponentLevel(int $opponentLevel, bool $won): array
    {
        $opponentLevel = max(1, $opponentLevel);
        $levelIndex = $opponentLevel - 1;

        $config = config('game.pvp.rewards');

        if ($won) {
            $cash = (int) $config['cash_win_base'] + $levelIndex * (int) $config['cash_per_opponent_level_win'];
            $reputation = (int) $config['reputation_win_base'] + $levelIndex * (int) $config['reputation_per_opponent_level_win'];
        } else {
            $cash = (int) $config['cash_loss_base'] + $levelIndex * (int) $config['cash_per_opponent_level_loss'];
            $reputation = (int) $config['reputation_loss_base'] + $levelIndex * (int) $config['reputation_per_opponent_level_loss'];
        }

        return [
            'cash' => min((int) $config['cash_max'], max((int) $config['cash_min'], $cash)),
            'reputation' => min((int) $config['reputation_max'], max((int) $config['reputation_min'], $reputation)),
        ];
    }
}
