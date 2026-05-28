<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Part;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartEquipService
{
    public function equip(User $user, Part $part, Car $car): Part
    {
        if ($part->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'part' => ['You do not own this part.'],
            ]);
        }

        if ($car->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'car' => ['You do not own this car.'],
            ]);
        }

        return DB::transaction(function () use ($user, $part, $car) {
            $part = Part::query()
                ->whereKey($part->id)
                ->lockForUpdate()
                ->firstOrFail();

            $car = Car::query()
                ->whereKey($car->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($part->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'part' => ['You do not own this part.'],
                ]);
            }

            if ($car->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'car' => ['You do not own this car.'],
                ]);
            }

            $part->loadMissing('partModel');
            $car->loadMissing('carModel');

            $this->assertEquipCompatible($part, $car);

            $incumbent = Part::query()
                ->where('car_id', $car->id)
                ->where('slot', $part->slot->value)
                ->where('id', '!=', $part->id)
                ->lockForUpdate()
                ->first();

            if ($incumbent !== null) {
                $incumbent->update(['car_id' => null]);
            }

            $part->update(['car_id' => $car->id]);

            return $part->fresh(['partModel']);
        });
    }

    public function unequip(User $user, Part $part, ?Car $expectedCar = null): Part
    {
        if ($part->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'part' => ['You do not own this part.'],
            ]);
        }

        if ($expectedCar !== null && $expectedCar->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'car' => ['You do not own this car.'],
            ]);
        }

        return DB::transaction(function () use ($user, $part, $expectedCar) {
            $part = Part::query()
                ->whereKey($part->id)
                ->lockForUpdate()
                ->firstOrFail();

            $expectedCar = $expectedCar !== null
                ? Car::query()->whereKey($expectedCar->id)->lockForUpdate()->firstOrFail()
                : null;

            if ($part->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'part' => ['You do not own this part.'],
                ]);
            }

            if ($expectedCar !== null && $expectedCar->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'car' => ['You do not own this car.'],
                ]);
            }

            if ($part->car_id === null) {
                throw ValidationException::withMessages([
                    'part' => ['This part is not equipped on a car.'],
                ]);
            }

            if ($expectedCar !== null && $part->car_id !== $expectedCar->id) {
                throw ValidationException::withMessages([
                    'part' => ['This part is no longer equipped on that car.'],
                ]);
            }

            $part->update(['car_id' => null]);

            return $part->fresh(['partModel']);
        });
    }

    private function assertEquipCompatible(Part $part, Car $car): void
    {
        $partModel = $part->partModel;
        $carModel = $car->carModel;

        $allowedSlots = $carModel->resolvedUpgradeSlots();
        if (! in_array($part->slot->value, $allowedSlots, true)) {
            throw ValidationException::withMessages([
                'part' => ['This car model does not support that upgrade slot.'],
            ]);
        }

        if ($carModel->class->rank() < $partModel->min_car_class->rank()) {
            throw ValidationException::withMessages([
                'part' => ['This part requires a higher class car.'],
            ]);
        }
    }
}
