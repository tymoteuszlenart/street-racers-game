<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DealerService
{
    public function purchase(User $user, CarModel $carModel): Car
    {
        $profile = $user->playerProfile ?? throw ValidationException::withMessages([
            'car_model' => 'Player profile not found.',
        ]);

        $this->assertCanPurchase($profile, $carModel);

        return DB::transaction(function () use ($user, $carModel) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $carModel->refresh();

            $this->assertCanPurchase($profile, $carModel);

            $profile->cash -= $carModel->price;
            $profile->save();

            $car = Car::query()->create([
                'user_id' => $user->id,
                'car_model_id' => $carModel->id,
                'acquired_via' => AcquiredVia::Dealer,
                'purchase_price' => $carModel->price,
            ]);

            if ($profile->active_car_id === null) {
                $profile->setActiveCarId($car->id);
            }

            return $car;
        });
    }

    private function assertCanPurchase(PlayerProfile $profile, CarModel $carModel): void
    {
        $carModel->assertPurchasableBy($profile);
    }
}
