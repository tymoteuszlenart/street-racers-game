<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Part;
use Illuminate\Database\Eloquent\Collection;

class ConditionWearService
{
    public function applyRaceWear(Car $car, int $damageMinPercent, int $damageMaxPercent): void
    {
        $percentLost = random_int($damageMinPercent, $damageMaxPercent);

        $this->applyCarWearPoints($car, $percentLost);

        $parts = Part::query()
            ->where('car_id', $car->id)
            ->lockForUpdate()
            ->get();

        $this->applyPartsWearPoints($parts, $percentLost);
    }

    public function applyCarWear(Car $car, int $damageMinPercent, int $damageMaxPercent): void
    {
        $percentLost = random_int($damageMinPercent, $damageMaxPercent);
        $this->applyCarWearPoints($car, $percentLost);
    }

    /**
     * @param  Collection<int, Part>  $parts
     */
    public function applyEquippedPartsWear(Collection $parts, int $damageMinPercent, int $damageMaxPercent): void
    {
        $percentLost = random_int($damageMinPercent, $damageMaxPercent);
        $this->applyPartsWearPoints($parts, $percentLost);
    }

    private function applyCarWearPoints(Car $car, int $percentLost): void
    {
        $damage = (int) floor($car->condition_max * ($percentLost / 100));
        $car->condition_current = max(0, $car->condition_current - $damage);
        $car->save();
    }

    /**
     * @param  Collection<int, Part>  $parts
     */
    private function applyPartsWearPoints(Collection $parts, int $percentLost): void
    {
        foreach ($parts as $part) {
            $this->applyPartWear($part, $percentLost);
        }
    }

    private function applyPartWear(Part $part, int $percentLost): void
    {
        $damage = (int) floor($part->condition_max * ($percentLost / 100));
        $newCurrent = $part->condition_current - $damage;

        if ($newCurrent <= 0) {
            $part->car_id = null;
            $part->condition_current = 0;
            $part->save();
            $part->delete();

            return;
        }

        $part->condition_current = $newCurrent;
        $part->save();
    }
}
