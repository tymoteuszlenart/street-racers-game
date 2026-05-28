<?php

namespace App\Services;

use App\DTOs\NpcRaceStartResult;
use App\DTOs\ResolvedRaceAttempt;
use App\Enums\RaceAttemptStatus;
use App\Enums\RaceAttemptType;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Exceptions\RaceStartRateLimitedException;
use App\Models\Car;
use App\Models\PlayerProfile;
use App\Models\Race;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class RaceService
{
    /** @var (callable(): float)|null */
    private $randomUnit;

    public function __construct(
        private readonly FuelService $fuelService,
        private readonly RaceScoreCalculator $scoreCalculator,
        private readonly TransactionService $transactionService,
        private readonly PlayerLevelService $playerLevelService,
        private readonly CarStatAggregator $carStatAggregator,
    ) {}

    /**
     * @param  callable(): float  $randomUnit  Returns a float in [0, 1).
     */
    public function withRandomUnit(callable $randomUnit): self
    {
        $clone = clone $this;
        $clone->randomUnit = $randomUnit;

        return $clone;
    }

    public function startNpcRace(User $user, Race $race, string $idempotencyKey): NpcRaceStartResult
    {
        $maxAttempts = 3;

        for ($attemptNumber = 1; $attemptNumber <= $maxAttempts; $attemptNumber++) {
            try {
                return $this->startNpcRaceOnce($user, $race, $idempotencyKey);
            } catch (Throwable $exception) {
                if ($attemptNumber < $maxAttempts && $this->isDeadlock($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new \RuntimeException('Unreachable race start retry loop.');
    }

    private function startNpcRaceOnce(User $user, Race $race, string $idempotencyKey): NpcRaceStartResult
    {
        try {
            $result = DB::transaction(function () use ($user, $race, $idempotencyKey) {
                $resolvedAttempt = $this->resolveOrCreateAttempt(
                    userId: $user->id,
                    idempotencyKey: $idempotencyKey,
                    attemptType: RaceAttemptType::Npc,
                    raceId: $race->id,
                    defenderUserId: null,
                );

                $attempt = $resolvedAttempt->attempt;

                if (! $resolvedAttempt->isNew) {
                    return $this->replayIfAlreadyFinished($attempt);
                }

                [$profile, $car] = $this->lockPlayerState($user->id);

                $this->assertRaceStartNotRateLimited($user->id);
                $this->assertNpcRaceEligible($profile, $car, $race);

                $this->fuelService->regenerate($profile);
                $this->fuelService->spend($profile, $race->fuel_cost);

                $randomFactor = $this->scoreCalculator->randomFactorInRange(
                    (float) $race->random_factor_variance,
                    $this->randomUnitCallable(),
                );

                $playerStats = $this->statsFromCar($car);
                $playerOutcome = $this->scoreCalculator->calculate(
                    $playerStats,
                    $profile->level,
                    $randomFactor,
                );

                $opponentOutcome = $this->scoreCalculator->calculate(
                    [
                        'power' => $race->opponent_power,
                        'acceleration' => $race->opponent_acceleration,
                        'grip' => $race->opponent_grip,
                        'handling' => $race->opponent_handling,
                        'condition_percent' => 100,
                    ],
                    1,
                    0,
                );

                $playerScore = $playerOutcome['score'];
                $opponentScore = $opponentOutcome['score'];
                $isTie = $playerScore === $opponentScore;
                $won = $playerScore > $opponentScore;

                $this->applyRewards($profile, $race, $won);
                $this->applyConditionDamage($car, $race);

                $raceResult = RaceResult::query()->create([
                    'user_id' => $user->id,
                    'attempt_type' => RaceAttemptType::Npc,
                    'race_id' => $race->id,
                    'pvp_race_id' => null,
                    'won' => $won,
                    'is_tie' => $isTie,
                    'player_score' => $playerScore,
                    'opponent_score' => $opponentScore,
                    'score_breakdown' => [
                        'player' => $playerOutcome['breakdown'],
                        'opponent' => $opponentOutcome['breakdown'],
                    ],
                    'random_factor' => $randomFactor,
                ]);

                $attempt->update([
                    'status' => RaceAttemptStatus::Succeeded,
                    'race_result_id' => $raceResult->id,
                    'error_code' => null,
                ]);

                $this->logNpcRaceTransactions($user->id, $profile, $race, $raceResult, $won);

                return new NpcRaceStartResult(
                    raceResult: $raceResult->fresh(),
                    raceAttempt: $attempt->fresh(),
                    replayed: false,
                );
            });

            if (! $result->replayed) {
                $this->recordRaceStartRateLimitHit($user->id);
            }

            return $result;
        } catch (RaceAttemptPendingException|RaceAttemptFailedException|IdempotencyKeyExpiredException $e) {
            throw $e;
        } catch (ValidationException $e) {
            $this->markAttemptFailed(
                userId: $user->id,
                idempotencyKey: $idempotencyKey,
                attemptType: RaceAttemptType::Npc,
                raceId: $race->id,
                defenderUserId: null,
                exception: $e,
            );

            throw $e;
        }
    }

    private function replayIfAlreadyFinished(RaceAttempt $attempt): NpcRaceStartResult
    {
        if ($attempt->status === RaceAttemptStatus::Succeeded) {
            $raceResult = $attempt->raceResult ?? RaceResult::query()->findOrFail($attempt->race_result_id);

            return new NpcRaceStartResult(
                raceResult: $raceResult,
                raceAttempt: $attempt,
                replayed: true,
            );
        }

        if ($attempt->status === RaceAttemptStatus::Failed) {
            throw new RaceAttemptFailedException($attempt->error_code);
        }

        if ($attempt->isExpired()) {
            throw new IdempotencyKeyExpiredException;
        }

        throw new RaceAttemptPendingException;
    }

    private function resolveOrCreateAttempt(
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

    public static function raceStartRateLimitKey(int $userId): string
    {
        return 'race-start:'.$userId;
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

    /**
     * @return array{0: PlayerProfile, 1: Car|null}
     */
    private function lockPlayerState(int $userId): array
    {
        $profile = PlayerProfile::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();

        $car = null;

        if ($profile->active_car_id !== null) {
            $car = Car::query()
                ->whereKey($profile->active_car_id)
                ->lockForUpdate()
                ->first();
        }

        return [$profile, $car];
    }

    private function assertNpcRaceEligible(PlayerProfile $profile, ?Car $car, Race $race): void
    {
        if ($car === null) {
            throw ValidationException::withMessages([
                'active_car' => 'Select an active car before racing.',
            ]);
        }

        if ($car->user_id !== $profile->user_id) {
            throw ValidationException::withMessages([
                'active_car' => 'Select an active car before racing.',
            ]);
        }

        if (! $race->active) {
            throw ValidationException::withMessages([
                'race' => 'This race is not available.',
            ]);
        }

        if ($race->unlock_level > $profile->level) {
            throw ValidationException::withMessages([
                'race' => 'Your level is too low for this race.',
            ]);
        }
    }

    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}
     */
    private function statsFromCar(Car $car): array
    {
        return $this->carStatAggregator->aggregate($car);
    }

    private function logNpcRaceTransactions(
        int $userId,
        PlayerProfile $profile,
        Race $race,
        RaceResult $raceResult,
        bool $won,
    ): void {
        $sourceType = $raceResult->getMorphClass();
        $sourceId = $raceResult->id;

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::NpcRace,
            currency: TransactionCurrency::Fuel,
            amount: -$race->fuel_cost,
            balanceAfter: $profile->fuel_current,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::NpcRace,
            currency: TransactionCurrency::Cash,
            amount: $won ? $race->cash_reward_win : $race->cash_reward_loss,
            balanceAfter: $profile->cash,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::NpcRace,
            currency: TransactionCurrency::Reputation,
            amount: $won ? $race->reputation_reward_win : $race->reputation_reward_loss,
            balanceAfter: $profile->reputation,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::NpcRace,
            currency: TransactionCurrency::Experience,
            amount: $won ? $race->experience_reward_win : $race->experience_reward_loss,
            balanceAfter: $profile->experience,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );
    }

    private function applyRewards(PlayerProfile $profile, Race $race, bool $won): void
    {
        if ($won) {
            $profile->cash += $race->cash_reward_win;
            $profile->reputation += $race->reputation_reward_win;
            $this->playerLevelService->addExperience($profile, $race->experience_reward_win);
        } else {
            $profile->cash += $race->cash_reward_loss;
            $profile->reputation += $race->reputation_reward_loss;
            $this->playerLevelService->addExperience(
                $profile,
                $race->experience_reward_loss,
            );
        }

        $profile->save();
    }

    private function applyConditionDamage(Car $car, Race $race): void
    {
        $percentLost = random_int(
            $race->condition_damage_min,
            $race->condition_damage_max,
        );

        $damage = (int) floor($car->condition_max * ($percentLost / 100));
        $car->condition_current = max(0, $car->condition_current - $damage);
        $car->save();
    }

    private function markAttemptFailed(
        int $userId,
        string $idempotencyKey,
        RaceAttemptType $attemptType,
        ?int $raceId,
        ?int $defenderUserId,
        Throwable $exception,
    ): void {
        $errorCode = $exception instanceof ValidationException
            ? 'validation_failed'
            : 'race_failed';

        DB::transaction(function () use ($userId, $idempotencyKey, $attemptType, $raceId, $defenderUserId, $errorCode) {
            $resolvedAttempt = $this->resolveOrCreateAttempt(
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

    private function assertRaceStartNotRateLimited(int $userId): void
    {
        $rateLimitKey = self::raceStartRateLimitKey($userId);
        $maxAttempts = (int) config('game.race.start_rate_limit_per_minute', 30);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            throw new RaceStartRateLimitedException(
                retryAfterSeconds: max(1, RateLimiter::availableIn($rateLimitKey)),
            );
        }
    }

    private function recordRaceStartRateLimitHit(int $userId): void
    {
        RateLimiter::hit(self::raceStartRateLimitKey($userId), 60);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }

    private function isDeadlock(Throwable $exception): bool
    {
        if ($exception instanceof QueryException) {
            $sqlState = $exception->errorInfo[0] ?? null;
            $driverCode = $exception->errorInfo[1] ?? null;

            if ($sqlState === '40001' || $driverCode === 1213) {
                return true;
            }
        }

        if ($exception instanceof \PDOException && (int) $exception->getCode() === 1213) {
            return true;
        }

        $previous = $exception->getPrevious();

        return $previous !== null && $this->isDeadlock($previous);
    }

    private function randomUnitCallable(): callable
    {
        return $this->randomUnit ?? fn (): float => mt_rand() / (mt_getrandmax() + 1);
    }
}
