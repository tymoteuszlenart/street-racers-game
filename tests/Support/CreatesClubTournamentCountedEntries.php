<?php

namespace Tests\Support;

use App\Models\Club;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use App\Models\User;

trait CreatesClubTournamentCountedEntries
{
    private function createCountedEntry(
        ClubTournament $tournament,
        Club $club,
        User $user,
        int $points,
        string $createdAt,
    ): ClubTournamentEntry {
        $entry = ClubTournamentEntry::query()->create([
            'club_tournament_id' => $tournament->id,
            'club_id' => $club->id,
            'user_id' => $user->id,
            'points' => $points,
            'counts_toward_club' => true,
        ]);
        $entry->created_at = $createdAt;
        $entry->saveQuietly();

        return $entry;
    }
}
