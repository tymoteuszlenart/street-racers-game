<?php

namespace App\Services;

use App\Models\PlayerProfile;

class PlayerLevelService
{
    public function addExperience(PlayerProfile $profile, int $amount): void
    {
        if ($amount === 0) {
            return;
        }

        $profile->experience += $amount;
        $this->syncLevel($profile);
    }

    public function syncLevel(PlayerProfile $profile): void
    {
        $maxLevel = (int) config('game.player.max_level', 50);
        $experiencePerLevel = (int) config('game.player.experience_per_level', 100);

        while ($profile->level < $maxLevel) {
            $requiredExperience = $profile->level * $experiencePerLevel;

            if ($profile->experience < $requiredExperience) {
                break;
            }

            $profile->level++;
        }
    }

    public function experienceRequiredForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return ($level - 1) * (int) config('game.player.experience_per_level', 100);
    }

    /**
     * @return array{current: int, required: int, next_level: int}|null
     */
    public function progressTowardNextLevel(PlayerProfile $profile): ?array
    {
        $maxLevel = (int) config('game.player.max_level', 50);

        if ($profile->level >= $maxLevel) {
            return null;
        }

        $floor = $this->experienceRequiredForLevel($profile->level);
        $ceiling = $profile->level * (int) config('game.player.experience_per_level', 100);

        return [
            'current' => $profile->experience - $floor,
            'required' => $ceiling - $floor,
            'next_level' => $profile->level + 1,
        ];
    }
}
