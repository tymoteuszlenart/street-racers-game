<?php

namespace Tests\Unit;

use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceStartRateLimitedException;
use App\Models\Race;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RaceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_npc_race_spends_fuel_and_creates_result(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);

        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);

        $initialCash = $profile->cash;
        $result = $service->startNpcRace($user, $race, (string) Str::uuid());

        $profile->refresh();
        $this->assertSame(90, $profile->fuel_current);
        $this->assertFalse($result->replayed);
        $this->assertTrue($result->raceResult->won);
        $this->assertGreaterThan($initialCash, $profile->cash);
        $this->assertSame(1, RaceResult::query()->count());
        $this->assertSame(1, RaceAttempt::query()->where('status', 'succeeded')->count());
        $this->assertSame(4, Transaction::query()->count());
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::NpcRace->value,
            'currency' => TransactionCurrency::Fuel->value,
            'amount' => -10,
            'source_id' => $result->raceResult->id,
        ]);
    }

    public function test_active_car_must_belong_to_player(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $otherUser = User::factory()->create();
        $otherCar = $otherUser->cars()->firstOrFail();

        $profile->forceFill(['active_car_id' => $otherCar->id])->save();

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);

        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);

        try {
            $service->startNpcRace($user, $race, (string) Str::uuid());
            $this->fail('Expected validation exception for foreign active car.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('active_car', $exception->errors());
        }

        $this->assertSame(0, RaceResult::query()->count());
    }

    public function test_duplicate_idempotency_key_returns_same_result(): void
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

        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);
        $key = (string) Str::uuid();

        $first = $service->startNpcRace($user, $race, $key);
        $second = $service->startNpcRace($user, $race, $key);

        $this->assertFalse($first->replayed);
        $this->assertTrue($second->replayed);
        $this->assertSame($first->raceResult->id, $second->raceResult->id);
        $this->assertSame(90, $profile->fresh()->fuel_current);
        $this->assertSame(1, RaceResult::query()->count());
        $this->assertSame(4, Transaction::query()->count());
    }

    public function test_duplicate_idempotency_key_for_different_race_is_rejected(): void
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

        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);
        $key = (string) Str::uuid();

        $first = $service->startNpcRace($user, $firstRace, $key);

        $this->expectException(IdempotencyKeyConflictException::class);

        try {
            $service->startNpcRace($user, $secondRace, $key);
        } finally {
            $this->assertSame($first->raceResult->race_id, $firstRace->id);
            $this->assertSame(90, $profile->fresh()->fuel_current);
            $this->assertSame(1, RaceResult::query()->count());
        }
    }

    public function test_succeeded_attempt_replays_after_idempotency_ttl_expires(): void
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

        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);
        $key = (string) Str::uuid();

        $first = $service->startNpcRace($user, $race, $key);

        RaceAttempt::query()
            ->where('idempotency_key', $key)
            ->update(['expires_at' => now()->subHour()]);

        $second = $service->startNpcRace($user, $race, $key);

        $this->assertTrue($second->replayed);
        $this->assertSame($first->raceResult->id, $second->raceResult->id);
        $this->assertSame(90, $profile->fresh()->fuel_current);
    }

    public function test_expired_pending_attempt_requires_new_key(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create(['fuel_cost' => 10]);
        $key = (string) Str::uuid();

        RaceAttempt::query()->create([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'attempt_type' => 'npc',
            'race_id' => $race->id,
            'status' => 'pending',
            'expires_at' => now()->subHour(),
        ]);

        $this->expectException(IdempotencyKeyExpiredException::class);

        app(RaceService::class)->startNpcRace($user, $race, $key);
    }

    public function test_npc_race_applies_condition_damage(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $car = $profile->activeCar()->firstOrFail();
        $initialCondition = $car->condition_current;

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
            'condition_damage_min' => 10,
            'condition_damage_max' => 10,
        ]);

        app(RaceService::class)
            ->withRandomUnit(fn (): float => 0.9)
            ->startNpcRace($user, $race, (string) Str::uuid());

        $expectedDamage = (int) floor($car->condition_max * 0.10);

        $this->assertSame(
            max(0, $initialCondition - $expectedDamage),
            $car->fresh()->condition_current,
        );
    }

    public function test_insufficient_fuel_marks_attempt_failed(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 0, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create(['fuel_cost' => 10]);
        $key = (string) Str::uuid();

        $service = app(RaceService::class);

        try {
            $service->startNpcRace($user, $race, $key);
            $this->fail('Expected validation exception for insufficient fuel.');
        } catch (ValidationException) {
            // expected
        }

        $attempt = RaceAttempt::query()->where('idempotency_key', $key)->first();
        $this->assertNotNull($attempt);
        $this->assertSame('failed', $attempt->status->value);
        $this->assertSame(0, RaceResult::query()->count());
        $this->assertSame(0, RaceAttempt::query()->where('status', 'pending')->count());
    }

    public function test_rate_limited_new_key_rolls_back_pending_attempt_creation(): void
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

        RateLimiter::clear(RaceService::raceStartRateLimitKey($user->id));

        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);
        $firstKey = (string) Str::uuid();
        $secondKey = (string) Str::uuid();

        $service->startNpcRace($user, $race, $firstKey);

        try {
            $service->startNpcRace($user, $race, $secondKey);
            $this->fail('Expected rate limit exception for brand new key.');
        } catch (RaceStartRateLimitedException) {
            // expected
        }

        $this->assertDatabaseMissing('race_attempts', [
            'user_id' => $user->id,
            'idempotency_key' => $secondKey,
        ]);
        $this->assertSame(1, RaceAttempt::query()->count());
        $this->assertSame(1, RaceResult::query()->count());
    }
}
