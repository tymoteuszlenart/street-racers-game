<?php

namespace Tests\Unit;

use App\Models\Race;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\RaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }
}
