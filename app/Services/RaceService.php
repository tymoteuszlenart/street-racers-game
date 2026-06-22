<?php

namespace App\Services;

use App\DTOs\NpcRaceStartResult;
use App\Enums\RaceAttemptStatus;
use App\Enums\RaceAttemptType;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Exceptions\RaceStartRateLimitedException;
use App\Models\Car;
use App\Models\PlayerProfile;
use App\Models\Race;
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
        private readonly RaceAttemptService $raceAttemptService,
        private readonly NpcOpponentScaler $npcOpponentScaler,
        private readonly ConditionWearService $conditionWearService,
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
                $resolvedAttempt = $this->raceAttemptService->resolveOrCreate(
                    userId: $user->id,
                    idempotencyKey: $idempotencyKey,
                    attemptType: RaceAttemptType::Npc,
                    raceId: $race->id,
                    defenderUserId: null,
                );

                $attempt = $resolvedAttempt->attempt;

                if (! $resolvedAttempt->isNew) {
                    return new NpcRaceStartResult(
                        raceResult: $this->raceAttemptService->raceResultForFinishedAttempt($attempt),
                        raceAttempt: $attempt,
                        replayed: true,
                    );
                }

                [$profile, $car] = $this->lockPlayerState($user->id);

                $this->assertRaceStartNotRateLimited($user->id);
                $this->assertNpcRaceEligible($profile, $car, $race);

                $this->fuelService->regenerate($profile);
                if (! $user->is_admin) {
                    $this->fuelService->spend($profile, $race->fuel_cost);
                }

                $variance = (float) $race->random_factor_variance;
                $randomUnit = $this->randomUnitCallable();
                $playerRandomFactor = $this->scoreCalculator->randomFactorInRange($variance, $randomUnit);
                $opponentRandomFactor = $this->scoreCalculator->randomFactorInRange($variance, $randomUnit);

                $playerStats = $this->statsFromCar($car);
                $scaledOpponent = $this->npcOpponentScaler->buildForRace(
                    $race,
                    $profile->level,
                    $playerStats,
                    $profile->driverStats(),
                );

                $playerOutcome = $this->scoreCalculator->calculate(
                    $playerStats,
                    $profile->driverStats(),
                    $playerRandomFactor,
                    $race->resolvedRaceType(),
                );

                $opponentOutcome = $this->scoreCalculator->calculate(
                    $scaledOpponent['car'],
                    $scaledOpponent['driver'],
                    $opponentRandomFactor,
                    $race->resolvedRaceType(),
                );

                $playerScore = $playerOutcome['score'];
                $opponentScore = $opponentOutcome['score'];
                $isTie = $playerScore === $opponentScore;
                $won = $playerScore > $opponentScore;

                $experienceGranted = $this->applyRewards($profile, $race, $won);
                $this->conditionWearService->applyRaceWear(
                    $car,
                    $race->condition_damage_min,
                    $race->condition_damage_max,
                );

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
                        'opponent_scaled' => $scaledOpponent,
                        'opponent_random_factor' => $opponentRandomFactor,
                        'rewards' => [
                            'experience' => $experienceGranted
                                ? ($won ? $race->experience_reward_win : $race->experience_reward_loss)
                                : 0,
                        ],
                    ],
                    'random_factor' => $playerRandomFactor,
                ]);

                $attempt->update([
                    'status' => RaceAttemptStatus::Succeeded,
                    'race_result_id' => $raceResult->id,
                    'error_code' => null,
                ]);

                $this->logNpcRaceTransactions($user->id, $profile, $race, $raceResult, $won, $experienceGranted);

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

    public static function raceStartRateLimitKey(int $userId): string
    {
        return 'race-start:'.$userId;
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
        bool $experienceGranted,
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

        if ($experienceGranted) {
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
    }

    private function applyRewards(PlayerProfile $profile, Race $race, bool $won): bool
    {
        $experienceGranted = $this->playerLevelService->canGainExperience($profile);

        if ($won) {
            $profile->cash += $race->cash_reward_win;
            $profile->reputation += $race->reputation_reward_win;

            if ($experienceGranted) {
                $this->playerLevelService->addExperience($profile, $race->experience_reward_win);
            }
        } else {
            $profile->cash += $race->cash_reward_loss;
            $profile->reputation += $race->reputation_reward_loss;

            if ($experienceGranted) {
                $this->playerLevelService->addExperience(
                    $profile,
                    $race->experience_reward_loss,
                );
            }
        }

        $profile->save();

        return $experienceGranted;
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

        $this->raceAttemptService->markPendingAttemptFailed(
            userId: $userId,
            idempotencyKey: $idempotencyKey,
            attemptType: $attemptType,
            raceId: $raceId,
            defenderUserId: $defenderUserId,
            errorCode: $errorCode,
        );
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
