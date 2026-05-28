<?php

namespace App\Services;

use App\Models\PlayerProfile;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class FuelService
{
    public function regenerate(PlayerProfile $profile, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        if ($profile->fuel_updated_at === null) {
            $profile->fuel_updated_at = $now;
            $profile->save();

            return;
        }

        $minutesPerUnit = (int) config('game.fuel.regeneration_minutes', 5);
        $amountPerTick = (int) config('game.fuel.regeneration_amount', 1);

        $minutesPassed = $profile->fuel_updated_at->diffInMinutes($now);

        if ($minutesPassed < $minutesPerUnit) {
            return;
        }

        $ticks = intdiv($minutesPassed, $minutesPerUnit);
        $fuelToAdd = $ticks * $amountPerTick;

        if ($fuelToAdd <= 0) {
            return;
        }

        $profile->fuel_current = min($profile->fuel_max, $profile->fuel_current + $fuelToAdd);
        $profile->fuel_updated_at = $profile->fuel_updated_at->copy()->addMinutes($ticks * $minutesPerUnit);
        $profile->save();
    }

    public function hasEnough(PlayerProfile $profile, int $cost): bool
    {
        return $profile->fuel_current >= $cost;
    }

    public function isTankFull(PlayerProfile $profile): bool
    {
        return $profile->fuel_current >= $profile->fuel_max;
    }

    public function spend(PlayerProfile $profile, int $cost): void
    {
        if (! $this->hasEnough($profile, $cost)) {
            throw ValidationException::withMessages([
                'fuel' => 'Not enough fuel for this race.',
            ]);
        }

        $profile->fuel_current -= $cost;
        $profile->save();
    }

    public function grant(PlayerProfile $profile, int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $before = $profile->fuel_current;
        $profile->fuel_current = min($profile->fuel_max, $profile->fuel_current + $amount);
        $granted = $profile->fuel_current - $before;
        $profile->save();

        return $granted;
    }
}
