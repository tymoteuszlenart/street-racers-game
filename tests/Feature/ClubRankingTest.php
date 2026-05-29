<?php

namespace Tests\Feature;

use App\Enums\ClubTournamentStatus;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournament;
use App\Models\User;
use App\Services\ClubPointService;
use App\Services\ClubTournamentSeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesClubTournamentCountedEntries;
use Tests\TestCase;

class ClubRankingTest extends TestCase
{
    use CreatesClubTournamentCountedEntries;
    use RefreshDatabase;

    public function test_rankings_order_by_points(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        $tournament = ClubTournament::factory()->create(['status' => ClubTournamentStatus::Active]);

        $low = Club::factory()->create(['name' => 'Low Points', 'slug' => 'low-points', 'points' => 10]);
        $high = Club::factory()->create(['name' => 'High Points', 'slug' => 'high-points', 'points' => 500]);
        $mid = Club::factory()->create(['name' => 'Mid Points', 'slug' => 'mid-points', 'points' => 100]);

        $this->createCountedEntry($tournament, $low, $user, 10, '2026-05-10 10:00:00');
        $this->createCountedEntry($tournament, $mid, $user, 100, '2026-05-11 10:00:00');
        $this->createCountedEntry($tournament, $high, $user, 500, '2026-05-12 10:00:00');

        ClubMember::factory()->owner()->create(['club_id' => $high->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('clubs.rankings'));

        $response->assertOk();
        $response->assertSee('Clubs ranked by club points.', false);
        $response->assertSeeInOrder(['High Points', 'Mid Points', 'Low Points']);
        $response->assertSee('(your club)', false);
    }

    public function test_rankings_use_tiebreak_when_points_are_equal(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        $tournament = ClubTournament::factory()->create(['status' => ClubTournamentStatus::Active]);

        $first = Club::factory()->create(['name' => 'First To Score', 'slug' => 'first-to-score', 'points' => 50]);
        $second = Club::factory()->create(['name' => 'Second To Score', 'slug' => 'second-to-score', 'points' => 50]);

        $this->createCountedEntry($tournament, $first, $user, 50, '2026-05-10 10:00:00');
        $this->createCountedEntry($tournament, $second, $user, 50, '2026-05-15 10:00:00');

        ClubMember::factory()->owner()->create(['club_id' => $first->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('clubs.rankings'));

        $response->assertOk();
        $response->assertSeeInOrder(['First To Score', 'Second To Score']);
    }

    public function test_rankings_exclude_clubs_with_zero_counted_season_points(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        $tournament = ClubTournament::factory()->create(['status' => ClubTournamentStatus::Active]);

        $scoring = Club::factory()->create(['name' => 'Active Racers', 'slug' => 'active-racers', 'points' => 25]);
        Club::factory()->create(['name' => 'Idle Crew', 'slug' => 'idle-crew', 'points' => 999]);

        $this->createCountedEntry($tournament, $scoring, $user, 25, '2026-05-10 10:00:00');

        $response = $this->actingAs($user)->get(route('clubs.rankings'));

        $response->assertOk();
        $response->assertSee('Active Racers');
        $response->assertDontSee('Idle Crew');
    }

    public function test_rankings_reflect_point_service_updates(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        $tournament = app(ClubTournamentSeasonService::class)->ensureCurrentSeasonExists();
        $club = Club::factory()->create(['name' => 'Rising Stars', 'slug' => 'rising-stars', 'points' => 0]);
        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $user->id]);

        $this->createCountedEntry($tournament, $club, $user, 250, '2026-05-10 10:00:00');
        app(ClubPointService::class)->setPointsFromCountedEntries($club, $tournament);

        $response = $this->actingAs($user)->get(route('clubs.rankings'));

        $response->assertOk();
        $response->assertSee('250');
        $response->assertSee('Rising Stars');
    }

    public function test_rankings_forbidden_below_level_ten(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('clubs.rankings'))->assertForbidden();
    }
}
