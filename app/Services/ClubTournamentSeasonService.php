<?php

namespace App\Services;

use App\Enums\ClubTournamentStatus;
use App\Models\ClubTournament;
use App\Support\GameWeek;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClubTournamentSeasonService
{
    public function active(): ClubTournament
    {
        return $this->ensureCurrentSeasonExists();
    }

    public function ensureCurrentSeasonExists(): ClubTournament
    {
        return DB::transaction(function () {
            $existing = ClubTournament::query()
                ->where('status', ClubTournamentStatus::Active)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $seasonKey = GameWeek::seasonKey();
            $byKey = ClubTournament::query()
                ->where('season_key', $seasonKey)
                ->lockForUpdate()
                ->first();

            if ($byKey !== null && $byKey->status === ClubTournamentStatus::Active) {
                return $byKey;
            }

            [$startsAt, $endsAt] = GameWeek::bounds();

            return ClubTournament::query()->create([
                'season_key' => $seasonKey,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => ClubTournamentStatus::Active,
            ]);
        });
    }

    public function createNextSeason(): ClubTournament
    {
        $nextInstant = GameWeek::currentEndsAt()->addSecond();
        $seasonKey = GameWeek::seasonKey($nextInstant->toMutable());
        [$startsAt, $endsAt] = GameWeek::bounds($nextInstant->toMutable());

        if (ClubTournament::query()->where('season_key', $seasonKey)->exists()) {
            throw ValidationException::withMessages([
                'season' => 'Next tournament season already exists.',
            ]);
        }

        return ClubTournament::query()->create([
            'season_key' => $seasonKey,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => ClubTournamentStatus::Active,
        ]);
    }
}
