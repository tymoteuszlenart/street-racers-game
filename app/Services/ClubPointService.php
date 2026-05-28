<?php

namespace App\Services;

use App\Models\Club;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClubPointService
{
    public function addPoints(Club $club, int $points): void
    {
        if ($points <= 0) {
            throw ValidationException::withMessages([
                'points' => 'Points must be a positive integer.',
            ]);
        }

        DB::transaction(function () use ($club, $points) {
            $club = Club::query()
                ->whereKey($club->id)
                ->lockForUpdate()
                ->firstOrFail();

            $club->increment('points', $points);
        });
    }
}
