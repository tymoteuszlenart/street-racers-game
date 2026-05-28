<?php

namespace App\Services;

use App\Models\Car;

class CarStatAggregator
{
    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}
     */
    public function aggregate(Car $car): array
    {
        $car->loadMissing(['carModel', 'parts.partModel']);
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

        $conditionPercent = $car->condition_max > 0
            ? ($car->condition_current / $car->condition_max) * 100
            : 100.0;

        return [
            'power' => $power,
            'acceleration' => $acceleration,
            'grip' => $grip,
            'handling' => $handling,
            'condition_percent' => $conditionPercent,
        ];
    }
}
