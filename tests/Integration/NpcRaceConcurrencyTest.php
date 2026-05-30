<?php

namespace Tests\Integration;

use App\Exceptions\RaceAttemptPendingException;
use App\Models\Race;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RaceService;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class NpcRaceConcurrencyTest extends TestCase
{
    public function test_full_npc_race_transaction_updates_profile_and_result(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);
        $initialCash = $profile->cash;

        $race = Race::query()->where('name', 'Amateur')->firstOrFail();
        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);

        $result = $service->startNpcRace($user, $race, (string) Str::uuid());

        $profile->refresh();
        $this->assertSame(100 - $race->fuel_cost, $profile->fuel_current);
        $this->assertTrue($result->raceResult->won);
        $this->assertGreaterThan($initialCash, $profile->cash);
        $this->assertDatabaseHas('race_results', ['id' => $result->raceResult->id]);
        $this->assertSame(4, Transaction::query()->where('source_id', $result->raceResult->id)->count());
    }

    public function test_duplicate_idempotency_key_replays_without_double_spend(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Amateur')->firstOrFail();
        $service = app(RaceService::class)->withRandomUnit(fn (): float => 0.9);
        $key = (string) Str::uuid();

        $first = $service->startNpcRace($user, $race, $key);
        $second = $service->startNpcRace($user, $race, $key);

        $this->assertTrue($second->replayed);
        $this->assertSame($first->raceResult->id, $second->raceResult->id);
        $this->assertSame(100 - $race->fuel_cost, $profile->fresh()->fuel_current);
        $this->assertSame(1, RaceResult::query()->count());
    }

    public function test_parallel_same_idempotency_key_produces_single_outcome(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Amateur')->firstOrFail();
        $key = (string) Str::uuid();

        $outputs = $this->runParallelNpcRaceStarts($user->id, $race->id, [$key, $key]);

        $resultIds = array_values(array_filter(array_map(
            fn (array $output) => $output['race_result_id'] ?? null,
            array_filter($outputs, fn (array $o) => $o['status'] === 'ok'),
        )));

        $this->assertNotEmpty($resultIds);
        $this->assertCount(1, array_unique($resultIds));
        $this->assertSame(100 - $race->fuel_cost, $profile->fresh()->fuel_current);
        $this->assertSame(1, RaceResult::query()->count());
    }

    public function test_parallel_different_keys_with_fuel_for_one_race_only_one_succeeds(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $race = Race::query()->where('name', 'Amateur')->firstOrFail();
        $profile->update(['fuel_current' => $race->fuel_cost, 'fuel_updated_at' => now()]);

        $outputs = $this->runParallelNpcRaceStarts($user->id, $race->id, [
            (string) Str::uuid(),
            (string) Str::uuid(),
        ]);

        $okCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'ok'));
        $errorCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'error'));

        $this->assertSame(1, $okCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(1, $errorCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(0, $profile->fresh()->fuel_current, 'Only one race should consume the exact starting fuel.');
        $this->assertSame(1, RaceResult::query()->count());
        $this->assertSame(1, RaceAttempt::query()->where('status', 'succeeded')->count());
        $this->assertSame(1, RaceAttempt::query()->where('status', 'failed')->count());
    }

    public function test_pending_attempt_returns_conflict_on_second_in_process_request(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Amateur')->firstOrFail();
        $key = (string) Str::uuid();

        RaceAttempt::query()->create([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'attempt_type' => 'npc',
            'race_id' => $race->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $this->expectException(RaceAttemptPendingException::class);

        app(RaceService::class)->startNpcRace($user, $race, $key);
    }

    /**
     * @param  list<string>  $idempotencyKeys
     * @return list<array<string, mixed>>
     */
    private function runParallelNpcRaceStarts(int $userId, int $raceId, array $idempotencyKeys): array
    {
        $processes = [];

        foreach ($idempotencyKeys as $key) {
            $process = new Process([
                PHP_BINARY,
                'artisan',
                'race:integration-npc-start',
                (string) $userId,
                (string) $raceId,
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
                $this->fail('Race subprocess produced no output: '.$process->getErrorOutput());
            }

            $outputs[] = json_decode($rawOutput, true, 512, JSON_THROW_ON_ERROR);
        }

        return $outputs;
    }
}
