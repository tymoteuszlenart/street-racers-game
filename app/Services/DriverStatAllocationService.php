<?php

namespace App\Services;

use App\Models\PlayerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverStatAllocationService
{
    /** @var list<string> */
    public const STAT_KEYS = ['power', 'acceleration', 'grip', 'handling'];

    /**
     * @param  array<string, int>  $increments
     */
    public function allocate(PlayerProfile $profile, array $increments): void
    {
        $normalized = $this->normalizeIncrements($increments);
        $total = array_sum($normalized);

        if ($total < 1) {
            throw ValidationException::withMessages([
                'stats' => __('Allocate at least one stat point.'),
            ]);
        }

        if ($total > $profile->unspent_stat_points) {
            throw ValidationException::withMessages([
                'stats' => __('You only have :count unspent stat point(s).', [
                    'count' => $profile->unspent_stat_points,
                ]),
            ]);
        }

        DB::transaction(function () use ($profile, $normalized, $total): void {
            $locked = PlayerProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if ($total > $locked->unspent_stat_points) {
                throw ValidationException::withMessages([
                    'stats' => __('You only have :count unspent stat point(s).', [
                        'count' => $locked->unspent_stat_points,
                    ]),
                ]);
            }

            foreach ($normalized as $stat => $amount) {
                if ($amount === 0) {
                    continue;
                }

                $column = 'stat_'.$stat;
                $locked->{$column} = (int) $locked->{$column} + $amount;
            }

            $locked->unspent_stat_points -= $total;
            $locked->save();

            $profile->setRawAttributes($locked->getAttributes());
            $profile->syncOriginal();
        });
    }

    /**
     * @param  array<string, int|string|null>  $increments
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    public function normalizeIncrements(array $increments): array
    {
        $normalized = [];

        foreach (self::STAT_KEYS as $stat) {
            $normalized[$stat] = max(0, (int) ($increments[$stat] ?? 0));
        }

        return $normalized;
    }
}
