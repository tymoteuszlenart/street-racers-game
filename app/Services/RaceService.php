<?php

namespace App\Services;

use App\DTOs\NpcRaceStartResult;
use App\Enums\RaceAttemptStatus;
use App\Enums\RaceAttemptType;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Models\Car;
use App\Models\PlayerProfile;
use App\Models\Race;
use App\Models\RaceAttempt;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RaceService
{
    /** @var (callable(): float)|null */
    private $randomUnit;

    public function __construct(
        private readonly FuelService $fuelService,
        private readonly RaceScoreCalculator $scoreCalculator,
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
            } catch (QueryException $exception) {
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
        $attempt = DB::transaction(fn () => $this->resolveOrCreateAttempt(
            userId: $user->id,
            idempotencyKey: $idempotencyKey,
            attemptType: RaceAttemptType::Npc,
            raceId: $race->id,
            defenderUserId: null,
        ));

        $replay = $this->replayIfAlreadyFinished($attempt);

        if ($replay !== null) {
            return $replay;
        }

        try {
            return DB::transaction(function () use ($user, $race, $attempt) {
                $attempt = RaceAttempt::query()
                    ->whereKey($attempt->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($attempt->status !== RaceAttemptStatus::Pending) {
                    throw new RaceAttemptPendingException;
                }

                $race = Race::query()->whereKey($race->id)->lockForUpdate()->firstOrFail();

                [$profile, $car] = $this->lockPlayerState($user->id);

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

                $won = $playerOutcome['score'] > $opponentOutcome['score'];

                $this->applyRewards($profile, $race, $won);
                $this->applyConditionDamage($car, $race);

                $raceResult = RaceResult::query()->create([
                    'user_id' => $user->id,
                    'attempt_type' => RaceAttemptType::Npc,
                    'race_id' => $race->id,
                    'pvp_race_id' => null,
                    'won' => $won,
                    'player_score' => $playerOutcome['score'],
                    'opponent_score' => $opponentOutcome['score'],
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

                return new NpcRaceStartResult(
                    raceResult: $raceResult->fresh(),
                    raceAttempt: $attempt->fresh(),
                    replayed: false,
                );
            });
        } catch (RaceAttemptPendingException|RaceAttemptFailedException|IdempotencyKeyExpiredException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->markAttemptFailed($attempt, $e);

            throw $e;
        }
    }

    private function replayIfAlreadyFinished(RaceAttempt $attempt): ?NpcRaceStartResult
    {
        if ($attempt->wasRecentlyCreated) {
            return null;
        }

        if ($attempt->isExpired()) {
            throw new IdempotencyKeyExpiredException;
        }

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

        throw new RaceAttemptPendingException;
    }

    private function resolveOrCreateAttempt(
        int $userId,
        string $idempotencyKey,
        RaceAttemptType $attemptType,
        ?int $raceId,
        ?int $defenderUserId,
    ): RaceAttempt {
        $attempt = RaceAttempt::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();

        if ($attempt !== null) {
            return $attempt;
        }

        $expiresAt = now()->addHours((int) config('game.race.idempotency_ttl_hours', 24));

        try {
            return RaceAttempt::query()->create([
                'user_id' => $userId,
                'idempotency_key' => $idempotencyKey,
                'attempt_type' => $attemptType,
                'race_id' => $raceId,
                'defender_user_id' => $defenderUserId,
                'status' => RaceAttemptStatus::Pending,
                'expires_at' => $expiresAt,
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            return RaceAttempt::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();
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
        $car->loadMissing('carModel');
        $model = $car->carModel;

        $conditionPercent = $car->condition_max > 0
            ? ($car->condition_current / $car->condition_max) * 100
            : 100;

        return [
            'power' => $model->power,
            'acceleration' => $model->acceleration,
            'grip' => $model->grip,
            'handling' => $model->handling,
            'condition_percent' => $conditionPercent,
        ];
    }

    private function applyRewards(PlayerProfile $profile, Race $race, bool $won): void
    {
        if ($won) {
            $profile->cash += $race->cash_reward_win;
            $profile->reputation += $race->reputation_reward_win;
            $profile->experience += $race->experience_reward_win;
        } else {
            $profile->cash += $race->cash_reward_loss;
            $profile->reputation += $race->reputation_reward_loss;
            $profile->experience += $race->experience_reward_loss;
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

    private function markAttemptFailed(?RaceAttempt $attempt, Throwable $exception): void
    {
        if ($attempt === null || $attempt->status !== RaceAttemptStatus::Pending) {
            return;
        }

        $errorCode = $exception instanceof ValidationException
            ? 'validation_failed'
            : 'race_failed';

        DB::transaction(function () use ($attempt, $errorCode) {
            $locked = RaceAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->status !== RaceAttemptStatus::Pending) {
                return;
            }

            $locked->update([
                'status' => RaceAttemptStatus::Failed,
                'error_code' => $errorCode,
            ]);
        });
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }

    private function isDeadlock(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '40001' || $driverCode === 1213;
    }

    private function randomUnitCallable(): callable
    {
        return $this->randomUnit ?? fn (): float => mt_rand() / (mt_getrandmax() + 1);
    }
}
