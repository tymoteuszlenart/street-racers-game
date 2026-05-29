<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ClubTournamentSeasonRankingService
{
    /**
     * Clubs with at least one counted season point, highest points first; ties broken by
     * when the club first reached its current total (chronological entry order), then club id.
     *
     * @return Collection<int, Club>
     */
    public function rankedClubsForSeason(ClubTournament $tournament): Collection
    {
        return Club::query()
            ->get()
            ->map(fn (Club $club) => [
                'club' => $club,
                'points' => $this->seasonPoints($club, $tournament),
                'tiebreak_at' => $this->tiebreakReachedAt($club, $tournament),
            ])
            ->filter(fn (array $row): bool => $row['points'] > 0)
            ->sort($this->compareRankedRows(...))
            ->pluck('club')
            ->values();
    }

    /**
     * @return Collection<int, Club>
     */
    public function topClubsForSeason(ClubTournament $tournament, int $limit): Collection
    {
        return $this->rankedClubsForSeason($tournament)->take($limit)->values();
    }

    public function seasonPoints(Club $club, ClubTournament $tournament): int
    {
        return (int) ClubTournamentEntry::query()
            ->where('club_id', $club->id)
            ->where('club_tournament_id', $tournament->id)
            ->where('counts_toward_club', true)
            ->sum('points');
    }

    /**
     * When the club first reached its current counted total (chronological entry order).
     */
    public function tiebreakReachedAt(Club $club, ClubTournament $tournament): ?CarbonInterface
    {
        $entries = ClubTournamentEntry::query()
            ->where('club_id', $club->id)
            ->where('club_tournament_id', $tournament->id)
            ->where('counts_toward_club', true)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['points', 'created_at']);

        $total = $entries->sum('points');

        if ($total === 0) {
            return null;
        }

        $running = 0;

        foreach ($entries as $entry) {
            $running += $entry->points;

            if ($running >= $total) {
                return $entry->created_at;
            }
        }

        return $entries->last()->created_at;
    }

    /**
     * @param  array{club: Club, points: int, tiebreak_at: ?CarbonInterface}  $a
     * @param  array{club: Club, points: int, tiebreak_at: ?CarbonInterface}  $b
     */
    private function compareRankedRows(array $a, array $b): int
    {
        if ($a['points'] !== $b['points']) {
            return $b['points'] <=> $a['points'];
        }

        $aTime = $a['tiebreak_at']?->getTimestamp() ?? PHP_INT_MAX;
        $bTime = $b['tiebreak_at']?->getTimestamp() ?? PHP_INT_MAX;

        if ($aTime !== $bTime) {
            return $aTime <=> $bTime;
        }

        return $a['club']->id <=> $b['club']->id;
    }
}
