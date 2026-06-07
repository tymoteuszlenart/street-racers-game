<?php

namespace Tests\Integration;

use App\Exceptions\RaceAttemptPendingException;
use App\Models\PvpRace;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\PvpRaceService;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PvpRaceConcurrencyTest extends TestCase
{
    public function test_full_pvp_race_transaction_spends_challenger_fuel_only(): void
    {
        $challenger = User::factory()->create();
        $defender = User::factory()->create();

        $challengerProfile = $challenger->playerProfile()->firstOrFail();
        $defenderProfile = $defender->playerProfile()->firstOrFail();
        $challengerProfile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);
        $defenderProfile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $defenderCar = $defenderProfile->activeCar()->firstOrFail();
        $defenderConditionBefore = $defenderCar->condition_current;
        $challengerCashBefore = $challengerProfile->cash;
        $challengerReputationBefore = $challengerProfile->reputation;
        $defenderCashBefore = $defenderProfile->cash;

        $service = app(PvpRaceService::class)->withRandomUnit(fn (): float => 0.5);
        $result = $service->startPvpRace($challenger, $defender, (string) Str::uuid());

        $challengerProfile->refresh();
        $defenderProfile->refresh();
        $defenderCar->refresh();

        $rewards = $result->raceResult->score_breakdown['rewards'];

        $this->assertSame(90, $challengerProfile->fuel_current);
        $this->assertSame(100, $defenderProfile->fuel_current);
        $this->assertSame($defenderConditionBefore, $defenderCar->condition_current);
        $this->assertSame($challengerCashBefore + $rewards['cash'], $challengerProfile->cash);
        $this->assertSame($challengerReputationBefore + $rewards['reputation'], $challengerProfile->reputation);
        $this->assertSame($defenderCashBefore, $defenderProfile->cash);
        $this->assertDatabaseHas('pvp_races', ['id' => $result->pvpRace->id]);
        $this->assertDatabaseHas('race_results', ['id' => $result->raceResult->id, 'pvp_race_id' => $result->pvpRace->id]);
    }

    public function test_duplicate_idempotency_key_replays_without_double_spend(): void
    {
        $challenger = User::factory()->create();
        $defender = User::factory()->create();
        $challenger->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $service = app(PvpRaceService::class)->withRandomUnit(fn (): float => 0.5);
        $key = (string) Str::uuid();

        $first = $service->startPvpRace($challenger, $defender, $key);
        $second = $service->startPvpRace($challenger, $defender, $key);

        $this->assertTrue($second->replayed);
        $this->assertSame($first->raceResult->id, $second->raceResult->id);
        $this->assertSame(90, $challenger->playerProfile()->firstOrFail()->fresh()->fuel_current);
        $this->assertSame(1, RaceResult::query()->count());
        $this->assertSame(1, PvpRace::query()->count());
    }

    public function test_parallel_same_idempotency_key_produces_single_outcome(): void
    {
        $challenger = User::factory()->create();
        $defender = User::factory()->create();
        $challenger->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $key = (string) Str::uuid();

        $outputs = $this->runParallelPvpRaceStarts($challenger->id, $defender->id, [$key, $key]);

        $resultIds = array_values(array_filter(array_map(
            fn (array $output) => $output['race_result_id'] ?? null,
            array_filter($outputs, fn (array $o) => $o['status'] === 'ok'),
        )));

        $this->assertNotEmpty($resultIds);
        $this->assertCount(1, array_unique($resultIds));
        $this->assertSame(90, $challenger->playerProfile()->firstOrFail()->fresh()->fuel_current);
        $this->assertSame(1, RaceResult::query()->count());
    }

    public function test_parallel_different_keys_with_fuel_for_one_race_only_one_succeeds(): void
    {
        $challenger = User::factory()->create();
        $defender = User::factory()->create();
        $challenger->playerProfile()->firstOrFail()->update([
            'fuel_current' => 10,
            'fuel_updated_at' => now(),
        ]);

        $outputs = $this->runParallelPvpRaceStarts($challenger->id, $defender->id, [
            (string) Str::uuid(),
            (string) Str::uuid(),
        ]);

        $okCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'ok'));
        $errorCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'error'));

        $this->assertSame(1, $okCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(1, $errorCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(0, $challenger->playerProfile()->firstOrFail()->fresh()->fuel_current);
        $this->assertSame(1, RaceResult::query()->count());
        $this->assertSame(1, RaceAttempt::query()->where('status', 'succeeded')->count());
        $this->assertSame(1, RaceAttempt::query()->where('status', 'failed')->count());
    }

    public function test_pending_attempt_returns_conflict_on_second_in_process_request(): void
    {
        $challenger = User::factory()->create();
        $defender = User::factory()->create();
        $challenger->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $key = (string) Str::uuid();

        RaceAttempt::query()->create([
            'user_id' => $challenger->id,
            'idempotency_key' => $key,
            'attempt_type' => 'pvp',
            'defender_user_id' => $defender->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $this->expectException(RaceAttemptPendingException::class);

        app(PvpRaceService::class)->startPvpRace($challenger, $defender, $key);
    }

    /**
     * @param  list<string>  $idempotencyKeys
     * @return list<array<string, mixed>>
     */
    private function runParallelPvpRaceStarts(int $challengerId, int $defenderId, array $idempotencyKeys): array
    {
        $processes = [];

        foreach ($idempotencyKeys as $key) {
            $process = new Process([
                PHP_BINARY,
                'artisan',
                'race:integration-pvp-start',
                (string) $challengerId,
                (string) $defenderId,
                $key,
            ], base_path(), null, null, 60);
            $process->start();
            $processes[] = $process;
        }

        $outputs = [];

        foreach ($processes as $process) {
            $process->wait();

            $rawOutput = trim($process->getOutput());

            if ($rawOutput === '') {
                $this->fail('PvP race subprocess produced no output: '.$process->getErrorOutput());
            }

            $outputs[] = json_decode($rawOutput, true, 512, JSON_THROW_ON_ERROR);
        }

        return $outputs;
    }
}
