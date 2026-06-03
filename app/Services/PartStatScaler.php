<?php

namespace App\Services;

class PartStatScaler
{
    public function multiplier(int $upgradeLevel): float
    {
        $perLevel = (int) config('game.mechanic.bonus_percent_per_level', 10);

        return 1 + ($upgradeLevel * $perLevel / 100);
    }

    public function scaledBonus(int $baseBonus, int $upgradeLevel): int
    {
        if ($baseBonus <= 0) {
            return 0;
        }

        return (int) floor($baseBonus * $this->multiplier($upgradeLevel));
    }
}
