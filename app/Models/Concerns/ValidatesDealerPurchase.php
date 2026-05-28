<?php

namespace App\Models\Concerns;

use App\Models\CarModel;
use App\Models\PlayerProfile;
use Illuminate\Validation\ValidationException;

trait ValidatesDealerPurchase
{
    /**
     * @throws ValidationException
     */
    public function assertPurchasableBy(PlayerProfile $profile): void
    {
        $errors = $this->purchasabilityErrors($profile);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function purchasabilityErrors(PlayerProfile $profile): array
    {
        /** @var CarModel $carModel */
        $carModel = $this;

        if ($carModel->starter) {
            return ['car_model' => ['Starter cars cannot be purchased from the dealer.']];
        }

        if (! $carModel->active) {
            return ['car_model' => ['This car is not available at the dealer.']];
        }

        if ($carModel->unlock_level > $profile->level) {
            return ['car_model' => ['Your level is too low to purchase this car.']];
        }

        if ($profile->cash < $carModel->price) {
            return ['cash' => ['You do not have enough cash for this car.']];
        }

        return [];
    }
}
