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
    public function purchase(User $user, CarModel $carModel, string $nickname): Car
    {
        $this->validatePurchase($user, $carModel);

        return DB::transaction(function () use ($user, $carModel, $nickname) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($profile->cash < $carModel->price) {
                throw ValidationException::withMessages([
                    'cash' => 'You do not have enough cash for this car.',
                ]);
            }

            $profile->cash -= $carModel->price;
            $profile->save();

            $car = Car::query()->create([
                'user_id' => $user->id,
                'car_model_id' => $carModel->id,
                'nickname' => $nickname,
                'acquired_via' => AcquiredVia::Dealer,
                'purchase_price' => $carModel->price,
            ]);

            if ($profile->active_car_id === null) {
                $profile->update(['active_car_id' => $car->id]);
            }

            return $car;
        });
    }

    private function validatePurchase(User $user, CarModel $carModel): void
    {
        if (! $carModel->active) {
            throw ValidationException::withMessages([
                'car_model' => 'This car is not available at the dealer.',
            ]);
        }

        $level = $user->playerProfile?->level ?? 1;

        if ($carModel->unlock_level > $level) {
            throw ValidationException::withMessages([
                'car_model' => 'Your level is too low to purchase this car.',
            ]);
        }

        if ($user->playerProfile?->cash < $carModel->price) {
            throw ValidationException::withMessages([
                'cash' => 'You do not have enough cash for this car.',
            ]);
        }
    }
}
