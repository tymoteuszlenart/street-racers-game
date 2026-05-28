<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClubTournamentScoringService
{
    public function __construct(
        private readonly ClubPointService $clubPointService,
    ) {}

    public function pointsForRace(bool $won, bool $isTie, float $playerScore, float $opponentScore): int
    {
        if ($isTie || ! $won) {
            return (int) config('game.tournaments.points_loss', 1);
        }

        $points = (int) config('game.tournaments.points_win', 3);
        $margin = $playerScore - $opponentScore;
        $perfectMargin = (float) config('game.tournaments.perfect_win_margin', 15);

        if ($margin >= $perfectMargin) {
            $points += (int) config('game.tournaments.points_perfect_bonus', 2);
        }

        return $points;
    }

    public function recalculateForUser(ClubTournament $tournament, User $user): void
    {
        DB::transaction(function () use ($tournament, $user) {
            $entries = ClubTournamentEntry::query()
                ->where('club_tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->orderByDesc('points')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $countedLimit = (int) config('game.tournaments.counted_attempts_per_player', 10);

            foreach ($entries as $index => $entry) {
                $entry->update([
                    'counts_toward_club' => $index < $countedLimit,
                ]);
            }

            $clubIds = $entries->pluck('club_id')->unique();

            foreach ($clubIds as $clubId) {
                $club = Club::query()->findOrFail($clubId);
                $this->recalculateClubPoints($club, $tournament);
            }
        });
    }

    public function recalculateClubPoints(Club $club, ClubTournament $tournament): void
    {
        $this->clubPointService->setPointsFromCountedEntries($club, $tournament);
    }
}
