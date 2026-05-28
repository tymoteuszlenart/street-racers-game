<?php

namespace App\Models\Concerns;

use App\Models\PartModel;
use App\Models\PlayerProfile;
use Illuminate\Validation\ValidationException;

trait ValidatesTuningPurchase
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
        /** @var PartModel $partModel */
        $partModel = $this;

        if (! $partModel->active) {
            return ['part_model' => ['This part is not available in the tuning shop.']];
        }

        if ($profile->level < 5) {
            return ['tuning' => ['Reach level 5 to access the tuning shop.']];
        }

        if ($partModel->unlock_level > $profile->level) {
            return ['part_model' => ['Your level is too low to purchase this part.']];
        }

        if ($profile->cash < $partModel->price) {
            return ['cash' => ['You do not have enough cash for this part.']];
        }

        return [];
    }
}
