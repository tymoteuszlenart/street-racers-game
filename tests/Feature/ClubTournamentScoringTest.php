<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournamentEntry;
use App\Models\User;
use App\Services\ClubTournamentScoringService;
use App\Services\ClubTournamentSeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubTournamentScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_best_ten_entries_count_toward_club_points(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create(['points' => 0]);
        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $user->id]);

        $tournament = app(ClubTournamentSeasonService::class)->ensureCurrentSeasonExists();
        $scoring = app(ClubTournamentScoringService::class);

        for ($i = 1; $i <= 15; $i++) {
            ClubTournamentEntry::query()->create([
                'club_tournament_id' => $tournament->id,
                'club_id' => $club->id,
                'user_id' => $user->id,
                'points' => $i,
                'counts_toward_club' => false,
            ]);
        }

        $scoring->recalculateForUser($tournament, $user);

        $counted = ClubTournamentEntry::query()
            ->where('user_id', $user->id)
            ->where('counts_toward_club', true)
            ->count();

        $this->assertSame(10, $counted);

        $club->refresh();
        $this->assertSame(105, $club->points);
    }
}
