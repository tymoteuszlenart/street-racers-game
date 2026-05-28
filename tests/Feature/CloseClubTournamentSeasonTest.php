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
use Tests\TestCase;

class CloseClubTournamentSeasonTest extends TestCase
{
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
}
