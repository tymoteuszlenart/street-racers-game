<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Exceptions\StarterCarCatalogNotConfiguredException;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StarterCarService
{
    public function assignToProfile(PlayerProfile $profile): Car
    {
        $user = $profile->user;

        if ($user->cars()->exists()) {
            $active = $profile->activeCar;
            if ($active !== null) {
                return $active;
            }

            $car = $user->cars()->orderBy('id')->firstOrFail();
            $profile->setActiveCarId($car->id);

            return $car;
        }

        $carModel = CarModel::query()
            ->active()
            ->starter()
            ->where('unlock_level', 1)
            ->orderBy('id')
            ->first();

        if ($carModel === null) {
            Log::error('Starter car catalog is not configured.', [
                'player_profile_id' => $profile->id,
                'user_id' => $user->id,
            ]);

            throw new StarterCarCatalogNotConfiguredException;
        }

        return DB::transaction(function () use ($profile, $user, $carModel) {
            $car = Car::query()->create([
                'user_id' => $user->id,
                'car_model_id' => $carModel->id,
                'nickname' => $this->generateNickname($user, $carModel),
                'acquired_via' => AcquiredVia::Starter,
                'purchase_price' => null,
            ]);

            $profile->setActiveCarId($car->id);

            return $car;
        });
    }

    private function generateNickname(User $user, CarModel $carModel): string
    {
        return "{$user->name}'s {$carModel->name}";
    }
}
