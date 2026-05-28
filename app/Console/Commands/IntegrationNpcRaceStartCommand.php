<?php

namespace App\Console\Commands;

use App\Models\Race;
use App\Models\User;
use App\Services\RaceService;
use Illuminate\Console\Command;
use Throwable;

class IntegrationNpcRaceStartCommand extends Command
{
    protected $signature = 'race:integration-npc-start {userId} {raceId} {idempotencyKey}';

    protected $description = 'Run an NPC race start for MySQL integration concurrency tests';

    public function handle(RaceService $raceService): int
    {
        $user = User::query()->findOrFail((int) $this->argument('userId'));
        $race = Race::query()->findOrFail((int) $this->argument('raceId'));
        $idempotencyKey = $this->argument('idempotencyKey');

        try {
            $result = $raceService->startNpcRace($user, $race, $idempotencyKey);

            $this->line(json_encode([
                'status' => 'ok',
                'race_result_id' => $result->raceResult->id,
                'replayed' => $result->replayed,
                'attempt_status' => $result->raceAttempt->status->value,
            ], JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line(json_encode([
                'status' => 'error',
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ], JSON_THROW_ON_ERROR));

            return self::FAILURE;
        }
    }
}
