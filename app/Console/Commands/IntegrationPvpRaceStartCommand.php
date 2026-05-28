<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PvpRaceService;
use Illuminate\Console\Command;
use Throwable;

class IntegrationPvpRaceStartCommand extends Command
{
    protected $signature = 'race:integration-pvp-start {challengerId} {defenderId} {idempotencyKey}';

    protected $description = 'Run a PvP race start for MySQL integration concurrency tests';

    public function handle(PvpRaceService $pvpRaceService): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command is only available in local and testing environments.');

            return self::FAILURE;
        }

        $challenger = User::query()->findOrFail((int) $this->argument('challengerId'));
        $defender = User::query()->findOrFail((int) $this->argument('defenderId'));
        $idempotencyKey = $this->argument('idempotencyKey');

        try {
            $result = $pvpRaceService->startPvpRace($challenger, $defender, $idempotencyKey);

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
