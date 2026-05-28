<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StarterCarService
{
    public function assignToProfile(PlayerProfile $profile): ?Car
    {
        $carModel = CarModel::query()
            ->active()
            ->starter()
            ->where('unlock_level', 1)
            ->orderBy('id')
            ->first();

        if ($carModel === null) {
            return null;
        }

        $user = $profile->user;

        return DB::transaction(function () use ($profile, $user, $carModel) {
            $car = Car::query()->create([
                'user_id' => $user->id,
                'car_model_id' => $carModel->id,
                'nickname' => $this->generateNickname($user, $carModel),
                'acquired_via' => AcquiredVia::Starter,
                'purchase_price' => null,
            ]);

            $profile->update(['active_car_id' => $car->id]);

            return $car;
        });
    }

    private function generateNickname(User $user, CarModel $carModel): string
    {
        return "{$user->name}'s {$carModel->name}";
    }
}
