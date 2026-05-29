<?php

namespace Tests\Unit;

use App\Models\Club;
use App\Models\ClubTournament;
use App\Models\User;
use App\Services\ClubTournamentSeasonRankingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesClubTournamentCountedEntries;
use Tests\TestCase;

class ClubTournamentSeasonRankingServiceTest extends TestCase
{
    use CreatesClubTournamentCountedEntries;
    use RefreshDatabase;

    private ClubTournamentSeasonRankingService $rankingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rankingService = app(ClubTournamentSeasonRankingService::class);
    }

    public function test_tiebreak_uses_when_club_first_reached_counted_total_not_club_updated_at(): void
    {
        Carbon::setTestNow('2026-05-20 12:00:00');

        $tournament = ClubTournament::factory()->create();
        $user = User::factory()->create();

        $clubFirst = Club::factory()->create([
            'points' => 30,
            'updated_at' => '2026-05-25 12:00:00',
        ]);
        $clubLater = Club::factory()->create([
            'points' => 30,
            'updated_at' => '2026-05-21 12:00:00',
        ]);

        $this->createCountedEntry($tournament, $clubFirst, $user, 30, '2026-05-18 10:00:00');
        $this->createCountedEntry($tournament, $clubLater, $user, 30, '2026-05-22 10:00:00');

        $ranked = $this->rankingService->topClubsForSeason($tournament, 2);

        $this->assertSame($clubFirst->id, $ranked[0]->id);
        $this->assertSame($clubLater->id, $ranked[1]->id);

        Carbon::setTestNow();
    }

    public function test_tiebreak_reached_at_is_chronological_sum_of_counted_entries(): void
    {
        $tournament = ClubTournament::factory()->create();
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $this->createCountedEntry($tournament, $club, $user, 10, '2026-05-10 08:00:00');
        $this->createCountedEntry($tournament, $club, $user, 20, '2026-05-12 08:00:00');

        $reachedAt = $this->rankingService->tiebreakReachedAt($club, $tournament);

        $this->assertNotNull($reachedAt);
        $this->assertSame('2026-05-12 08:00:00', $reachedAt->toDateTimeString());
        $this->assertSame(30, $this->rankingService->seasonPoints($club, $tournament));
    }

    public function test_ranked_clubs_exclude_zero_point_clubs(): void
    {
        $tournament = ClubTournament::factory()->create();
        $user = User::factory()->create();

        $scoringClub = Club::factory()->create(['points' => 5]);
        $emptyClub = Club::factory()->create(['points' => 100]);

        $this->createCountedEntry($tournament, $scoringClub, $user, 5, '2026-05-18 10:00:00');

        $ranked = $this->rankingService->rankedClubsForSeason($tournament);

        $this->assertCount(1, $ranked);
        $this->assertSame($scoringClub->id, $ranked->first()->id);
        $this->assertEmpty($this->rankingService->topClubsForSeason($tournament, 3)->where('id', $emptyClub->id));
    }
}
