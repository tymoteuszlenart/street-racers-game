<?php

namespace App\Services;

class OpenCupRewardCalculator
{
    public function soloCupsForWins(int $wins): int
    {
        return match (min(max($wins, 0), 3)) {
            3 => 3,
            2 => 2,
            1 => 1,
            default => 0,
        };
    }

    /**
     * @return array{cash: int, cups: int}
     */
    public function participationRewards(): array
    {
        return [
            'cash' => (int) config('game.open_cup.participation_cash', 1200),
            'cups' => (int) config('game.open_cup.participation_cups', 1),
        ];
    }

    public function championCash(int $entryFee, int $entrantCount): int
    {
        $otherEntrants = max(0, $entrantCount - 1);
        $share = (float) config('game.open_cup.champion_pot_share', 0.40);

        return (int) floor($entryFee * $otherEntrants * $share);
    }

    public function bracketWinCups(): int
    {
        return (int) config('game.open_cup.bracket_win_cups', 3);
    }

    /**
     * @return array{cash: int, cups: int}
     */
    public function soloRewardsForWins(int $wins, int $entryFee): array
    {
        $participation = $this->participationRewards();

        return [
            'cash' => $participation['cash'],
            'cups' => $participation['cups'] + $this->soloCupsForWins($wins),
        ];
    }
}
