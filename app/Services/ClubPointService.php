<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClubPointService
{
    /**
     * Club rankings tie-break: higher points first, then earlier updated_at (club that reached the score first).
     */
    public function setPointsFromCountedEntries(Club $club, ClubTournament $tournament): void
    {
        DB::transaction(function () use ($club, $tournament) {
            $club = Club::query()
                ->whereKey($club->id)
                ->lockForUpdate()
                ->firstOrFail();

            $total = ClubTournamentEntry::query()
                ->where('club_id', $club->id)
                ->where('club_tournament_id', $tournament->id)
                ->where('counts_toward_club', true)
                ->sum('points');

            $club->update(['points' => (int) $total]);
        });
    }

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
