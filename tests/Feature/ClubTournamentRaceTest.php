<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use App\Models\User;
use App\Services\ClubTournamentSeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
