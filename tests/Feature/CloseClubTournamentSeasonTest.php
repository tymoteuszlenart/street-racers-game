<?php

namespace Tests\Feature;

use App\Enums\ClubTournamentStatus;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournament;
use App\Models\ClubTournamentRewardGrant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\CreatesClubTournamentCountedEntries;
use Tests\TestCase;

class CloseClubTournamentSeasonTest extends TestCase
{
    use CreatesClubTournamentCountedEntries;
    use RefreshDatabase;

    public function test_close_command_distributes_rewards_idempotently(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $tournament = ClubTournament::factory()->create([
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subMinute(),
            'status' => ClubTournamentStatus::Active,
        ]);

        $club = Club::factory()->create(['points' => 100]);
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 15, 'cash' => 1000]);
        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $user->id]);

        $this->createCountedEntry($tournament, $club, $user, 100, '2026-05-20 10:00:00');

        Artisan::call('club-tournament:close');
        Artisan::call('club-tournament:close');

        $this->assertSame(1, ClubTournamentRewardGrant::query()->where('user_id', $user->id)->count());
        $this->assertSame(6000, $user->playerProfile->fresh()->cash);
        $this->assertSame(ClubTournamentStatus::RewardsDistributed, $tournament->fresh()->status);
        $this->assertSame(0, $club->fresh()->points);
        $this->assertTrue(
            ClubTournament::query()->where('status', ClubTournamentStatus::Active)->exists(),
        );

        Carbon::setTestNow();
    }

    public function test_close_command_awards_first_place_to_club_that_reached_tied_score_first(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $tournament = ClubTournament::factory()->create([
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subMinute(),
            'status' => ClubTournamentStatus::Active,
        ]);

        $winnerClub = Club::factory()->create([
            'points' => 50,
            'updated_at' => '2026-05-24 12:00:00',
        ]);
        $runnerUpClub = Club::factory()->create([
            'points' => 50,
            'updated_at' => '2026-05-20 12:00:00',
        ]);

        $winnerUser = User::factory()->create();
        $winnerUser->playerProfile()->update(['level' => 15, 'cash' => 1000]);
        ClubMember::factory()->owner()->create(['club_id' => $winnerClub->id, 'user_id' => $winnerUser->id]);

        $runnerUpUser = User::factory()->create();
        $runnerUpUser->playerProfile()->update(['level' => 15, 'cash' => 1000]);

        $this->createCountedEntry($tournament, $winnerClub, $winnerUser, 50, '2026-05-18 10:00:00');
        $this->createCountedEntry($tournament, $runnerUpClub, $runnerUpUser, 50, '2026-05-22 10:00:00');

        Artisan::call('club-tournament:close');

        $this->assertSame(6000, $winnerUser->playerProfile->fresh()->cash);
        $this->assertSame(1000, $runnerUpUser->playerProfile->fresh()->cash);
        $this->assertTrue(
            ClubTournamentRewardGrant::query()
                ->where('club_tournament_id', $tournament->id)
                ->where('user_id', $winnerUser->id)
                ->exists(),
        );
        $this->assertFalse(
            ClubTournamentRewardGrant::query()
                ->where('club_tournament_id', $tournament->id)
                ->where('user_id', $runnerUpUser->id)
                ->exists(),
        );

        Carbon::setTestNow();
    }

    public function test_close_command_skips_club_with_zero_counted_season_points(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $tournament = ClubTournament::factory()->create([
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subMinute(),
            'status' => ClubTournamentStatus::Active,
        ]);

        $club = Club::factory()->create(['points' => 100]);
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 15, 'cash' => 1000]);
        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $user->id]);

        Artisan::call('club-tournament:close');

        $this->assertSame(1000, $user->playerProfile->fresh()->cash);
        $this->assertFalse(
            ClubTournamentRewardGrant::query()
                ->where('club_tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->exists(),
        );

        Carbon::setTestNow();
    }
}
