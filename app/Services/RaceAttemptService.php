<?php

namespace App\Services;

use App\DTOs\ResolvedRaceAttempt;
use App\Enums\RaceAttemptStatus;
use App\Enums\RaceAttemptType;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RaceAttemptService
{
    public function resolveOrCreate(
        int $userId,
        string $idempotencyKey,
        RaceAttemptType $attemptType,
        ?int $raceId,
        ?int $defenderUserId,
    ): ResolvedRaceAttempt {
        $attempt = RaceAttempt::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();

        if ($attempt !== null) {
            $this->assertMatchingAttemptRequest($attempt, $attemptType, $raceId, $defenderUserId);

            return new ResolvedRaceAttempt(
                attempt: $attempt,
                isNew: false,
            );
        }

        $expiresAt = now()->addHours((int) config('game.race.idempotency_ttl_hours', 24));

        try {
            return new ResolvedRaceAttempt(
                attempt: RaceAttempt::query()->create([
                    'user_id' => $userId,
                    'idempotency_key' => $idempotencyKey,
                    'attempt_type' => $attemptType,
                    'race_id' => $raceId,
                    'defender_user_id' => $defenderUserId,
                    'status' => RaceAttemptStatus::Pending,
                    'expires_at' => $expiresAt,
                ]),
                isNew: true,
            );
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $attempt = RaceAttempt::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertMatchingAttemptRequest($attempt, $attemptType, $raceId, $defenderUserId);

            return new ResolvedRaceAttempt(
                attempt: $attempt,
                isNew: false,
            );
        }
    }

    public function raceResultForFinishedAttempt(RaceAttempt $attempt): RaceResult
    {
        if ($attempt->status === RaceAttemptStatus::Succeeded) {
            return $attempt->raceResult ?? RaceResult::query()->findOrFail($attempt->race_result_id);
        }

        if ($attempt->status === RaceAttemptStatus::Failed) {
            throw new RaceAttemptFailedException($attempt->error_code);
        }

        if ($attempt->isExpired()) {
            throw new IdempotencyKeyExpiredException;
        }

        throw new RaceAttemptPendingException;
    }

    public function markPendingAttemptFailed(
        int $userId,
        string $idempotencyKey,
        RaceAttemptType $attemptType,
        ?int $raceId,
        ?int $defenderUserId,
        string $errorCode,
    ): void {
        DB::transaction(function () use ($userId, $idempotencyKey, $attemptType, $raceId, $defenderUserId, $errorCode) {
            $resolvedAttempt = $this->resolveOrCreate(
                userId: $userId,
                idempotencyKey: $idempotencyKey,
                attemptType: $attemptType,
                raceId: $raceId,
                defenderUserId: $defenderUserId,
            );

            $attempt = $resolvedAttempt->attempt;

            if ($attempt->status !== RaceAttemptStatus::Pending) {
                return;
            }

            $attempt->update([
                'status' => RaceAttemptStatus::Failed,
                'error_code' => $errorCode,
            ]);
        });
    }

    private function assertMatchingAttemptRequest(
        RaceAttempt $attempt,
        RaceAttemptType $attemptType,
        ?int $raceId,
        ?int $defenderUserId,
    ): void {
        if (
            $attempt->attempt_type !== $attemptType
            || $attempt->race_id !== $raceId
            || $attempt->defender_user_id !== $defenderUserId
        ) {
            throw new IdempotencyKeyConflictException;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
