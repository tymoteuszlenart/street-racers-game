<?php

namespace App\Services;

use App\Models\Car;
use App\Models\PlayerProfile;

class OpenCupSnapshotBuilder
{
    public function __construct(
        private readonly PvpRaceSnapshotBuilder $pvpSnapshotBuilder,
    ) {}

    /**
     * @return array{
     *     car_id: int,
     *     car_model_id: int,
     *     car_name: string,
     *     stats: array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float},
     *     driver: array{power: int, acceleration: int, grip: int, handling: int},
     *     level: int
     * }
     */
    public function build(PlayerProfile $profile, Car $car): array
    {
        return [
            ...$this->pvpSnapshotBuilder->build($car),
            'driver' => $profile->driverStats(),
            'level' => $profile->level,
        ];
    }
}
