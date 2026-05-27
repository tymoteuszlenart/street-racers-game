<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\PlayerProfile;
use App\Models\User;

class StarterCarService
{
    public function assignToProfile(PlayerProfile $profile): ?Car
    {
        $carModel = CarModel::query()
            ->active()
            ->starter()
            ->where('unlock_level', 1)
            ->first();

        if ($carModel === null) {
            return null;
        }

        $user = $profile->user;

        $car = Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carModel->id,
            'nickname' => $this->generateNickname($user, $carModel),
            'acquired_via' => AcquiredVia::Starter,
            'purchase_price' => null,
        ]);

        $profile->update(['active_car_id' => $car->id]);

        return $car;
    }

    private function generateNickname(User $user, CarModel $carModel): string
    {
        return "{$user->name}'s {$carModel->name}";
    }
}
