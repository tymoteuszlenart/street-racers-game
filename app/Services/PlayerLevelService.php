<?php

namespace App\Services;

use App\Models\PlayerProfile;

class PlayerLevelService
{
    public function addExperience(PlayerProfile $profile, int $amount): void
    {
        if ($amount === 0 || ! $this->canGainExperience($profile)) {
            return;
        }

        $profile->experience += $amount;
        $this->syncLevel($profile);
        $this->clampExperience($profile);
    }

    public function syncLevel(PlayerProfile $profile): void
    {
        $maxLevel = $this->maxLevel();
        $previousLevel = $profile->level;

        while ($profile->level < $maxLevel) {
            $requiredExperience = $this->cumulativeExperienceForLevel($profile->level + 1);

            if ($profile->experience < $requiredExperience) {
                break;
            }

            $profile->level++;
        }

        if ($profile->level > $previousLevel) {
            $this->grantStatPointsForLevels($profile, $previousLevel, $profile->level);
        }

        $this->clampExperience($profile);
    }

    public function canGainExperience(PlayerProfile $profile): bool
    {
        return $profile->level < $this->maxLevel();
    }

    public function maxLevel(): int
    {
        return (int) config('game.player.max_level', 100);
    }

    public function experienceMultiplier(): int
    {
        return (int) config('game.player.experience.multiplier', 50);
    }

    /**
     * XP required for the single step into level $level (from level $level - 1).
     */
    public function experienceCostForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return $this->experienceMultiplier() * $level * $level;
    }

    /**
     * Minimum total XP when the player is at level $level.
     */
    public function cumulativeExperienceForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        $sumOfSquares = (int) (($level * ($level + 1) * (2 * $level + 1)) / 6);

        return $this->experienceMultiplier() * ($sumOfSquares - 1);
    }

    public function maxExperience(): int
    {
        return $this->cumulativeExperienceForLevel($this->maxLevel());
    }

    public function grantStatPointsForLevels(PlayerProfile $profile, int $fromLevel, int $toLevel): void
    {
        if ($toLevel <= $fromLevel) {
            return;
        }

        $pointsPerLevel = (int) config('game.player.driver_stats.points_per_level', 3);
        $levelsGained = $toLevel - $fromLevel;

        $profile->unspent_stat_points += $levelsGained * $pointsPerLevel;
    }

    public function experienceRequiredForLevel(int $level): int
    {
        return $this->cumulativeExperienceForLevel($level);
    }

    /**
     * @return array{current: int, required: int, next_level: int}|null
     */
    public function progressTowardNextLevel(PlayerProfile $profile): ?array
    {
        if ($profile->level >= $this->maxLevel()) {
            return null;
        }

        $floor = $this->cumulativeExperienceForLevel($profile->level);
        $ceiling = $this->cumulativeExperienceForLevel($profile->level + 1);

        return [
            'current' => $profile->experience - $floor,
            'required' => $ceiling - $floor,
            'next_level' => $profile->level + 1,
        ];
    }

    private function clampExperience(PlayerProfile $profile): void
    {
        $profile->experience = min($profile->experience, $this->maxExperience());
    }
}
