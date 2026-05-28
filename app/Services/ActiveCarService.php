<?php

namespace App\Services;

use App\Models\Car;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActiveCarService
{
    public function setActive(User $user, Car $car): void
    {
        $profile = $user->playerProfile ?? throw ValidationException::withMessages([
            'car' => 'Player profile not found.',
        ]);

        $profile->setActiveCarId($car->id);
    }
}
