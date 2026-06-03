<?php

namespace App\Services;

use App\Models\Car;

class PvpRaceSnapshotBuilder
{
    public function __construct(
        private readonly CarStatAggregator $carStatAggregator,
    ) {}

    /**
     * @return array{car_id: int, car_model_id: int, car_name: string, stats: array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}}
     */
    public function build(Car $car): array
    {
        $car->loadMissing(['carModel', 'parts.partModel']);

        return [
            'car_id' => $car->id,
            'car_model_id' => $car->car_model_id,
            'car_name' => $car->carModel->name,
            'stats' => $this->carStatAggregator->aggregate($car),
        ];
    }
}
