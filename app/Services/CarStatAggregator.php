<?php

namespace App\Services;

use App\Models\Car;

class CarStatAggregator
{
    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float, level_penalty_percent: int}
     */
    public function aggregate(Car $car): array
    {
        $car->loadMissing(['carModel', 'parts.partModel', 'user.playerProfile']);
        $model = $car->carModel;

        $power = $model->power;
        $acceleration = $model->acceleration;
        $grip = $model->grip;
        $handling = $model->handling;

        foreach ($car->parts as $part) {
            $bonus = $part->partModel;
            $power += $bonus->power_bonus;
            $acceleration += $bonus->acceleration_bonus;
            $grip += $bonus->grip_bonus;
            $handling += $bonus->handling_bonus;
        }

        $levelPenaltyPercent = $this->levelPenaltyPercent($car);

        if ($levelPenaltyPercent > 0) {
            $power = $this->applyLevelPenalty($power, $levelPenaltyPercent);
            $acceleration = $this->applyLevelPenalty($acceleration, $levelPenaltyPercent);
            $grip = $this->applyLevelPenalty($grip, $levelPenaltyPercent);
            $handling = $this->applyLevelPenalty($handling, $levelPenaltyPercent);
        }

        $conditionPercent = $car->condition_max > 0
            ? ($car->condition_current / $car->condition_max) * 100
            : 100.0;

        return [
            'power' => $power,
            'acceleration' => $acceleration,
            'grip' => $grip,
            'handling' => $handling,
            'condition_percent' => $conditionPercent,
            'level_penalty_percent' => $levelPenaltyPercent,
        ];
    }

    private function levelPenaltyPercent(Car $car): int
    {
        $playerLevel = $car->user?->playerProfile?->level;

        if ($playerLevel === null) {
            return 0;
        }

        $missingLevels = $car->carModel->unlock_level - $playerLevel;

        return max(0, min(5, $missingLevels)) * 10;
    }

    private function applyLevelPenalty(int $stat, int $penaltyPercent): int
    {
        return max(1, intdiv($stat * (100 - $penaltyPercent), 100));
    }
}
