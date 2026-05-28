<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use App\Models\User;
use App\Services\ClubTournamentSeasonService;
use App\Services\RaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClubTournamentRaceTest extends TestCase
{
    use RefreshDatabase;

    private function tournamentRacer(): array
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'level' => 15,
            'premium_fuel_current' => 3,
            'premium_fuel_max' => 5,
        ]);

        $club = Club::factory()->create();
        ClubMember::factory()->owner()->create([
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);

        app(ClubTournamentSeasonService::class)->ensureCurrentSeasonExists();

        return [$user, $club];
    }

    public function test_tournament_race_spends_premium_fuel(): void
    {
        [$user, $club] = $this->tournamentRacer();

        $response = $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect();
        $this->assertSame(2, $user->playerProfile->fresh()->premium_fuel_current);
        $this->assertSame(1, ClubTournamentEntry::query()->where('user_id', $user->id)->count());
    }

    public function test_duplicate_idempotency_key_does_not_double_spend_premium_fuel(): void
    {
        [$user, $club] = $this->tournamentRacer();
        $key = (string) Str::uuid();

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => $key,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => $key,
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'race-existing-result');

        $this->assertSame(2, $user->playerProfile->fresh()->premium_fuel_current);
        $this->assertSame(1, ClubTournamentEntry::query()->where('user_id', $user->id)->count());
    }

    public function test_tournament_race_start_is_rate_limited(): void
    {
        config(['game.race.start_rate_limit_per_minute' => 2]);

        [$user, $club] = $this->tournamentRacer();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['premium_fuel_current' => 10]);

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $blockedKey = (string) Str::uuid();

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => $blockedKey,
        ])->assertStatus(429);

        $this->assertDatabaseMissing('race_attempts', [
            'user_id' => $user->id,
            'idempotency_key' => $blockedKey,
        ]);
    }

    public function test_idempotent_tournament_replay_bypasses_rate_limit(): void
    {
        config(['game.race.start_rate_limit_per_minute' => 1]);

        [$user, $club] = $this->tournamentRacer();
        $key = (string) Str::uuid();

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => $key,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(429);

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => $key,
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'race-existing-result');
    }

    public function test_tournament_page_requires_tournament_unlock_level(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 12]);

        $club = Club::factory()->create();
        ClubMember::factory()->create([
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);

        app(ClubTournamentSeasonService::class)->ensureCurrentSeasonExists();

        $this->actingAs($user)
            ->get(route('clubs.tournament', $club))
            ->assertForbidden();
    }

    public function test_tournament_race_rejected_after_season_ends(): void
    {
        [$user, $club] = $this->tournamentRacer();
        ClubTournament::query()->firstOrFail()->update([
            'ends_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertSessionHasErrors('tournament');
        $this->assertSame(0, ClubTournamentEntry::query()->where('user_id', $user->id)->count());
        $this->assertSame(3, $user->playerProfile->fresh()->premium_fuel_current);
    }

    public function test_twenty_first_attempt_is_rejected(): void
    {
        [$user, $club] = $this->tournamentRacer();
        $tournament = ClubTournament::query()->firstOrFail();

        for ($i = 0; $i < 20; $i++) {
            ClubTournamentEntry::query()->create([
                'club_tournament_id' => $tournament->id,
                'club_id' => $club->id,
                'user_id' => $user->id,
                'points' => 1,
                'counts_toward_club' => false,
            ]);
        }

        $response = $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertSessionHasErrors('attempts');
    }

    public function test_tournament_result_page_shows_points(): void
    {
        [$user, $club] = $this->tournamentRacer();

        $this->actingAs($user)->post(route('clubs.tournament.races.store', $club), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $entry = ClubTournamentEntry::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('tournament-results.show', $entry->race_result_id))
            ->assertOk()
            ->assertSee((string) $entry->points);
    }
}
