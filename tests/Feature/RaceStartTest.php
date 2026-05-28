<?php

namespace Tests\Feature;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\Race;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\RaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class RaceStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_player_can_start_race_with_idempotency_key(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $response = $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect();
        $this->assertSame(90, $profile->fresh()->fuel_current);
    }

    public function test_insufficient_fuel_is_rejected(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 0, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('fuel');
        $this->assertSame(0, $profile->fresh()->fuel_current);
    }

    public function test_missing_active_car_is_rejected(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->forceFill([
            'active_car_id' => null,
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ])->save();

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('active_car');
    }

    public function test_reusing_idempotency_key_for_different_race_returns_conflict(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $firstRace = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);
        $secondRace = Race::factory()->create([
            'fuel_cost' => 15,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);
        $key = (string) Str::uuid();

        $this->actingAs($user)->post(route('races.start', $firstRace), [
            'idempotency_key' => $key,
        ])->assertRedirect();

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $secondRace), [
            'idempotency_key' => $key,
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('race');

        $this->assertSame(90, $profile->fresh()->fuel_current);
    }

    public function test_failed_idempotency_key_shows_error_on_retry(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 0, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();
        $key = (string) Str::uuid();

        $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => $key,
        ])->assertRedirect(route('races.index'))->assertSessionHasErrors('fuel');

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => $key,
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('race');
        $this->assertSame(0, RaceResult::query()->count());
    }

    public function test_validation_failure_allows_retry_with_fresh_idempotency_key(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 0, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('races.index'))->assertSessionHasErrors('fuel');

        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertSame(1, RaceResult::query()->count());
        $this->assertSame(90, $profile->fresh()->fuel_current);
    }

    public function test_race_start_is_rate_limited(): void
    {
        config(['game.race.start_rate_limit_per_minute' => 2]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $secondKey = (string) Str::uuid();

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => $secondKey,
        ])->assertStatus(429);

        $this->assertDatabaseMissing('race_attempts', [
            'user_id' => $user->id,
            'idempotency_key' => $secondKey,
        ]);
    }

    public function test_idempotent_replay_bypasses_rate_limit(): void
    {
        config(['game.race.start_rate_limit_per_minute' => 1]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);
        $key = (string) Str::uuid();

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => $key,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(429);

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => $key,
        ])->assertRedirect()->assertSessionHas('status', 'race-existing-result');
    }

    public function test_failed_key_returns_domain_error_even_after_quota_is_exhausted(): void
    {
        config(['game.race.start_rate_limit_per_minute' => 1]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 0, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);
        $failedKey = (string) Str::uuid();

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => $failedKey,
        ])->assertRedirect(route('races.index'))->assertSessionHasErrors('fuel');

        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(429);

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => $failedKey,
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('race');
    }

    public function test_inactive_race_cannot_be_started(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create([
            'active' => false,
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('race');
        $this->assertSame(0, RaceResult::query()->count());
    }

    public function test_pending_idempotency_key_returns_409_for_json_clients(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);
        $key = (string) Str::uuid();

        RaceAttempt::query()->create([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'attempt_type' => 'npc',
            'race_id' => $race->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->postJson(route('races.start', $race), ['idempotency_key' => $key])
            ->assertStatus(409)
            ->assertJson(['message' => 'A race with this idempotency key is already in progress.']);
    }

    public function test_race_score_reflects_equipped_part(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $car = $profile->activeCar()->firstOrFail();

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'random_factor_variance' => 0,
            'opponent_power' => 500,
            'opponent_acceleration' => 500,
            'opponent_grip' => 500,
            'opponent_handling' => 500,
        ]);

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $baselineScore = RaceResult::query()->firstOrFail()->player_score;

        $partModel = PartModel::factory()->create([
            'slot' => PartSlot::Engine,
            'power_bonus' => 40,
            'acceleration_bonus' => 40,
            'grip_bonus' => 40,
            'handling_bonus' => 40,
        ]);

        Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $profile->update(['fuel_current' => 100]);

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $upgradedScore = RaceResult::query()->orderByDesc('id')->firstOrFail()->player_score;

        $this->assertGreaterThan($baselineScore, $upgradedScore);
    }

    public function test_duplicate_submit_for_same_race_shows_existing_result_message(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();
        $key = (string) Str::uuid();

        $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => $key,
        ])->assertRedirect();

        $response = $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => $key,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'race-existing-result');
        $this->assertSame(90, $profile->fresh()->fuel_current);
    }
}
