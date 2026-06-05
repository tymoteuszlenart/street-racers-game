<?php

namespace App\Services;

use App\DTOs\PvpRaceStartResult;
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
use App\Models\PvpRace;
use App\Models\RaceResult;
use App\Models\User;
use App\Support\GameDay;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class PvpRaceService
{
    /** @var (callable(): float)|null */
    private $randomUnit;

    public function __construct(
        private readonly FuelService $fuelService,
        private readonly RaceScoreCalculator $scoreCalculator,
        private readonly RaceAttemptService $raceAttemptService,
        private readonly PvpRaceSnapshotBuilder $snapshotBuilder,
        private readonly ConditionWearService $conditionWearService,
        private readonly PvpRaceRewardCalculator $rewardCalculator,
        private readonly TransactionService $transactionService,
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

    public function startPvpRace(User $challenger, User $defender, string $idempotencyKey): PvpRaceStartResult
    {
        $maxAttempts = 3;

        for ($attemptNumber = 1; $attemptNumber <= $maxAttempts; $attemptNumber++) {
            try {
                return $this->startPvpRaceOnce($challenger, $defender, $idempotencyKey);
            } catch (Throwable $exception) {
                if ($attemptNumber < $maxAttempts && $this->isDeadlock($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new \RuntimeException('Unreachable PvP race start retry loop.');
    }

    private function startPvpRaceOnce(User $challenger, User $defender, string $idempotencyKey): PvpRaceStartResult
    {
        try {
            $result = DB::transaction(function () use ($challenger, $defender, $idempotencyKey) {
                $resolvedAttempt = $this->raceAttemptService->resolveOrCreate(
                    userId: $challenger->id,
                    idempotencyKey: $idempotencyKey,
                    attemptType: RaceAttemptType::Pvp,
                    raceId: null,
                    defenderUserId: $defender->id,
                );

                $attempt = $resolvedAttempt->attempt;

                if (! $resolvedAttempt->isNew) {
                    $raceResult = $this->raceAttemptService->raceResultForFinishedAttempt($attempt);
                    $pvpRace = $raceResult->pvpRace ?? PvpRace::query()->where('race_result_id', $raceResult->id)->firstOrFail();

                    return new PvpRaceStartResult(
                        raceResult: $raceResult,
                        pvpRace: $pvpRace,
                        raceAttempt: $attempt,
                        replayed: true,
                    );
                }

                $playerStates = $this->lockBothPlayersState($challenger->id, $defender->id);

                $this->assertRaceStartNotRateLimited($challenger->id);
                $this->assertPvpRaceEligible(
                    challengerProfile: $playerStates[$challenger->id]['profile'],
                    challengerCar: $playerStates[$challenger->id]['car'],
                    defenderProfile: $playerStates[$defender->id]['profile'],
                    defenderCar: $playerStates[$defender->id]['car'],
                    challengerId: $challenger->id,
                    defenderId: $defender->id,
                );

                $this->assertSamePairDailyCapNotExceeded($challenger->id, $defender->id);

                $challengerProfile = $playerStates[$challenger->id]['profile'];
                $challengerCar = $playerStates[$challenger->id]['car'];
                $defenderCar = $playerStates[$defender->id]['car'];

                $this->fuelService->regenerate($challengerProfile);
                $this->fuelService->spend($challengerProfile, $this->fuelCost());

                $challengerSnapshot = $this->snapshotBuilder->build($challengerCar);
                $defenderSnapshot = $this->snapshotBuilder->build($defenderCar);

                $pvpRace = PvpRace::query()->create([
                    'challenger_user_id' => $challenger->id,
                    'defender_user_id' => $defender->id,
                    'challenger_car_id' => $challengerCar->id,
                    'defender_car_id' => $defenderCar->id,
                    'challenger_snapshot' => $challengerSnapshot,
                    'defender_snapshot' => $defenderSnapshot,
                ]);

                $randomFactor = $this->scoreCalculator->randomFactorInRange(
                    $this->randomFactorVariance(),
                    $this->randomUnitCallable(),
                );

                $challengerOutcome = $this->scoreCalculator->calculate(
                    $challengerSnapshot['stats'],
                    $challengerProfile->driverStats(),
                    $randomFactor,
                );

                $defenderOutcome = $this->scoreCalculator->calculate(
                    $defenderSnapshot['stats'],
                    $playerStates[$defender->id]['profile']->driverStats(),
                    $randomFactor,
                );

                $playerScore = $challengerOutcome['score'];
                $opponentScore = $defenderOutcome['score'];
                $isTie = $playerScore === $opponentScore;
                $won = $playerScore > $opponentScore;

                $defenderProfile = $playerStates[$defender->id]['profile'];
                $rewards = $this->rewardCalculator->forOpponentLevel($defenderProfile->level, $won);

                $this->applyRewards($challengerProfile, $rewards);

                $raceResult = RaceResult::query()->create([
                    'user_id' => $challenger->id,
                    'attempt_type' => RaceAttemptType::Pvp,
                    'race_id' => null,
                    'pvp_race_id' => $pvpRace->id,
                    'won' => $won,
                    'is_tie' => $isTie,
                    'player_score' => $playerScore,
                    'opponent_score' => $opponentScore,
                    'score_breakdown' => [
                        'player' => $challengerOutcome['breakdown'],
                        'opponent' => $defenderOutcome['breakdown'],
                        'rewards' => [
                            ...$rewards,
                            'opponent_level' => $defenderProfile->level,
                        ],
                    ],
                    'random_factor' => $randomFactor,
                ]);

                $pvpRace->update(['race_result_id' => $raceResult->id]);

                $attempt->update([
                    'status' => RaceAttemptStatus::Succeeded,
                    'race_result_id' => $raceResult->id,
                    'error_code' => null,
                ]);

                $this->logPvpRaceTransactions(
                    $challenger->id,
                    $challengerProfile,
                    $raceResult,
                    $rewards,
                );

                $this->conditionWearService->applyRaceWear(
                    $challengerCar,
                    (int) config('game.pvp.condition_damage_min', 1),
                    (int) config('game.pvp.condition_damage_max', 3),
                );

                return new PvpRaceStartResult(
                    raceResult: $raceResult->fresh(),
                    pvpRace: $pvpRace->fresh(),
                    raceAttempt: $attempt->fresh(),
                    replayed: false,
                );
            });

            if (! $result->replayed) {
                $this->recordRaceStartRateLimitHit($challenger->id);
            }

            return $result;
        } catch (RaceAttemptPendingException|RaceAttemptFailedException|IdempotencyKeyExpiredException $e) {
            throw $e;
        } catch (ValidationException $e) {
            $this->raceAttemptService->markPendingAttemptFailed(
                userId: $challenger->id,
                idempotencyKey: $idempotencyKey,
                attemptType: RaceAttemptType::Pvp,
                raceId: null,
                defenderUserId: $defender->id,
                errorCode: 'validation_failed',
            );

            throw $e;
        }
    }

    /**
     * @return array<int, array{profile: PlayerProfile, car: Car|null}>
     */
    private function lockBothPlayersState(int $firstUserId, int $secondUserId): array
    {
        $userIds = [$firstUserId, $secondUserId];
        sort($userIds);

        $states = [];

        foreach ($userIds as $userId) {
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

            $states[$userId] = [
                'profile' => $profile,
                'car' => $car,
            ];
        }

        return $states;
    }

    private function assertPvpRaceEligible(
        PlayerProfile $challengerProfile,
        ?Car $challengerCar,
        PlayerProfile $defenderProfile,
        ?Car $defenderCar,
        int $challengerId,
        int $defenderId,
    ): void {
        if ($challengerId === $defenderId) {
            throw ValidationException::withMessages([
                'defender' => 'You cannot race yourself.',
            ]);
        }

        if ($challengerCar === null || $challengerCar->user_id !== $challengerProfile->user_id) {
            throw ValidationException::withMessages([
                'active_car' => 'Select an active car before racing.',
            ]);
        }

        if ($defenderCar === null || $defenderCar->user_id !== $defenderProfile->user_id) {
            throw ValidationException::withMessages([
                'defender' => 'This opponent does not have an active car.',
            ]);
        }
    }

    private function assertSamePairDailyCapNotExceeded(int $challengerId, int $defenderId): void
    {
        $cap = (int) config('game.pvp.daily_pair_cap', 10);

        if ($cap <= 0) {
            return;
        }

        [$dayStart, $dayEnd] = GameDay::bounds();

        $count = PvpRace::query()
            ->where(function ($query) use ($challengerId, $defenderId) {
                $query->where(function ($query) use ($challengerId, $defenderId) {
                    $query->where('challenger_user_id', $challengerId)
                        ->where('defender_user_id', $defenderId);
                })->orWhere(function ($query) use ($challengerId, $defenderId) {
                    $query->where('challenger_user_id', $defenderId)
                        ->where('defender_user_id', $challengerId);
                });
            })
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->count();

        if ($count >= $cap) {
            throw ValidationException::withMessages([
                'pvp' => 'Daily race limit reached for this opponent pair.',
            ]);
        }
    }

    private function fuelCost(): int
    {
        return (int) config('game.pvp.fuel_cost', 10);
    }

    private function randomFactorVariance(): float
    {
        return (float) config('game.pvp.random_factor_variance', 0.05);
    }

    private function assertRaceStartNotRateLimited(int $userId): void
    {
        $rateLimitKey = RaceService::raceStartRateLimitKey($userId);
        $maxAttempts = (int) config('game.race.start_rate_limit_per_minute', 30);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            throw new RaceStartRateLimitedException(
                retryAfterSeconds: max(1, RateLimiter::availableIn($rateLimitKey)),
            );
        }
    }

    private function recordRaceStartRateLimitHit(int $userId): void
    {
        RateLimiter::hit(RaceService::raceStartRateLimitKey($userId), 60);
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

    /**
     * @param  array{cash: int, reputation: int}  $rewards
     */
    private function applyRewards(PlayerProfile $profile, array $rewards): void
    {
        $profile->cash += $rewards['cash'];
        $profile->reputation += $rewards['reputation'];
        $profile->save();
    }

    /**
     * @param  array{cash: int, reputation: int}  $rewards
     */
    private function logPvpRaceTransactions(
        int $userId,
        PlayerProfile $profile,
        RaceResult $raceResult,
        array $rewards,
    ): void {
        $sourceType = $raceResult->getMorphClass();
        $sourceId = $raceResult->id;

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::PvpRace,
            currency: TransactionCurrency::Fuel,
            amount: -$this->fuelCost(),
            balanceAfter: $profile->fuel_current,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::PvpRace,
            currency: TransactionCurrency::Cash,
            amount: $rewards['cash'],
            balanceAfter: $profile->cash,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::PvpRace,
            currency: TransactionCurrency::Reputation,
            amount: $rewards['reputation'],
            balanceAfter: $profile->reputation,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );
    }
}
