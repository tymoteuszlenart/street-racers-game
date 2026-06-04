<?php

namespace App\Services;

use App\Models\Car;

class CarStatAggregator
{
    public function __construct(
        private readonly PartStatScaler $partStatScaler,
        private readonly ConditionService $conditionService,
    ) {}

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
            $level = $part->upgrade_level;
            $factor = $this->conditionService->partStatFactor(
                $part->condition_current,
                $part->condition_max,
            );
            $power += $this->scaledPartBonus($bonus->power_bonus, $level, $factor);
            $acceleration += $this->scaledPartBonus($bonus->acceleration_bonus, $level, $factor);
            $grip += $this->scaledPartBonus($bonus->grip_bonus, $level, $factor);
            $handling += $this->scaledPartBonus($bonus->handling_bonus, $level, $factor);
        }

        $levelPenaltyPercent = $this->levelPenaltyPercent($car);

        if ($levelPenaltyPercent > 0) {
            $power = $this->applyLevelPenalty($power, $levelPenaltyPercent);
            $acceleration = $this->applyLevelPenalty($acceleration, $levelPenaltyPercent);
            $grip = $this->applyLevelPenalty($grip, $levelPenaltyPercent);
            $handling = $this->applyLevelPenalty($handling, $levelPenaltyPercent);
        }

        $conditionPercent = $this->conditionService->percent(
            $car->condition_current,
            $car->condition_max,
        );

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

    private function scaledPartBonus(int $bonus, int $upgradeLevel, float $conditionFactor): int
    {
        $scaled = $this->partStatScaler->scaledBonus($bonus, $upgradeLevel);

        return (int) round($scaled * $conditionFactor);
    }
}
