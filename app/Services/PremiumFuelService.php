<?php

namespace App\Services;

use App\Models\PlayerProfile;
use Illuminate\Validation\ValidationException;

class PremiumFuelService
{
    public function storageMax(PlayerProfile $profile): int
    {
        return min(
            $profile->premium_fuel_max,
            (int) config('game.premium_fuel.default_max', 5),
        );
    }

    public function hasEnough(PlayerProfile $profile, int $cost): bool
    {
        return $profile->premium_fuel_current >= $cost;
    }

    public function isAtCap(PlayerProfile $profile): bool
    {
        return $profile->premium_fuel_current >= $this->storageMax($profile);
    }

    public function spend(PlayerProfile $profile, int $cost): void
    {
        if (! $this->hasEnough($profile, $cost)) {
            throw ValidationException::withMessages([
                'premium_fuel' => 'Not enough premium fuel for this tournament race.',
            ]);
        }

        $profile->premium_fuel_current -= $cost;
        $profile->save();
    }

    public function grant(PlayerProfile $profile, int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $max = $this->storageMax($profile);
        $before = $profile->premium_fuel_current;
        $profile->premium_fuel_current = min($max, $profile->premium_fuel_current + $amount);
        $granted = $profile->premium_fuel_current - $before;
        $profile->save();

        return $granted;
    }
}
