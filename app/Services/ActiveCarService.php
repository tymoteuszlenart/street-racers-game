<?php

namespace App\Services;

use App\Models\Car;
use App\Models\User;

class ActiveCarService
{
    public function setActive(User $user, Car $car): void
    {
        if ($car->user_id !== $user->id) {
            abort(403);
        }

        $user->playerProfile?->update(['active_car_id' => $car->id]);
    }
}
