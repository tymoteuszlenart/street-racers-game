<?php

namespace App\Services;

use App\DTOs\ClubTournamentRaceStartResult;
use App\Enums\RaceAttemptStatus;
use App\Enums\RaceAttemptType;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Exceptions\RaceStartRateLimitedException;
use App\Models\Car;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournament;
use App\Models\ClubTournamentEntry;
use App\Models\PlayerProfile;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClubTournamentRaceService
{
    /** @var (callable(): float)|null */
    private $randomUnit;

    public function __construct(
        private readonly PremiumFuelService $premiumFuelService,
        private readonly RaceScoreCalculator $scoreCalculator,
        private readonly TransactionService $transactionService,
        private readonly CarStatAggregator $carStatAggregator,
        private readonly RaceAttemptService $raceAttemptService,
        private readonly ClubTournamentSeasonService $seasonService,
        private readonly ClubTournamentScoringService $scoringService,
        private readonly ConditionWearService $conditionWearService,
    ) {}

    /**
     * @param  callable(): float  $randomUnit
     */
    public function withRandomUnit(callable $randomUnit): self
    {
        $clone = clone $this;
        $clone->randomUnit = $randomUnit;

        return $clone;
    }

    public function start(User $user, Club $club, string $idempotencyKey): ClubTournamentRaceStartResult
    {
        $maxAttempts = 3;

        for ($attemptNumber = 1; $attemptNumber <= $maxAttempts; $attemptNumber++) {
            try {
                $result = $this->startOnce($user, $club, $idempotencyKey);

                if (! $result->replayed) {
                    $this->recordRaceStartRateLimitHit($user->id);
                }

                return $result;
            } catch (Throwable $exception) {
                if ($attemptNumber < $maxAttempts && $this->isDeadlock($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new \RuntimeException('Unreachable tournament race start retry loop.');
    }

    private function startOnce(User $user, Club $club, string $idempotencyKey): ClubTournamentRaceStartResult
    {
        $tournament = $this->seasonService->active();

        try {
            return DB::transaction(function () use ($user, $club, $idempotencyKey, $tournament) {
                $resolvedAttempt = $this->raceAttemptService->resolveOrCreate(
                    userId: $user->id,
                    idempotencyKey: $idempotencyKey,
                    attemptType: RaceAttemptType::ClubTournament,
                    raceId: null,
                    defenderUserId: null,
                    clubTournamentId: $tournament->id,
                );

                $attempt = $resolvedAttempt->attempt;

                if (! $resolvedAttempt->isNew) {
                    $raceResult = $this->raceAttemptService->raceResultForFinishedAttempt($attempt);
                    $entry = ClubTournamentEntry::query()
                        ->where('race_result_id', $raceResult->id)
                        ->firstOrFail();

                    return new ClubTournamentRaceStartResult(
                        raceResult: $raceResult,
                        raceAttempt: $attempt,
                        entry: $entry,
                        replayed: true,
                    );
                }

                [$profile, $car, $membership] = $this->lockPlayerState($user->id);

                $this->assertRaceStartNotRateLimited($user->id);
                $this->assertEligible($profile, $car, $club, $membership, $tournament);

                $entryCost = (int) config('game.premium_fuel.tournament_entry_cost', 1);
                $this->premiumFuelService->spend($profile, $entryCost);

                $variance = (float) config('game.tournaments.random_factor_variance', 0.03);
                $randomFactor = $this->scoreCalculator->randomFactorInRange(
                    $variance,
                    $this->randomUnitCallable(),
                );

                $playerStats = $this->carStatAggregator->aggregate($car);
                $raceType = $this->scoreCalculator->defaultRaceType();
                $playerOutcome = $this->scoreCalculator->calculate(
                    $playerStats,
                    $profile->driverStats(),
                    $randomFactor,
                    $raceType,
                );

                $opponentConfig = config('game.tournaments.opponent');
                $opponentOutcome = $this->scoreCalculator->calculate(
                    [
                        'power' => (int) $opponentConfig['power'],
                        'acceleration' => (int) $opponentConfig['acceleration'],
                        'grip' => (int) $opponentConfig['grip'],
                        'handling' => (int) $opponentConfig['handling'],
                        'condition_percent' => 100,
                    ],
                    config('game.player.driver_stats.base', []),
                    0,
                    $raceType,
                );

                $playerScore = $playerOutcome['score'];
                $opponentScore = $opponentOutcome['score'];
                $isTie = $playerScore === $opponentScore;
                $won = $playerScore > $opponentScore;

                $this->conditionWearService->applyRaceWear(
                    $car,
                    (int) config('game.tournaments.condition_damage_min', 2),
                    (int) config('game.tournaments.condition_damage_max', 5),
                );

                $raceResult = RaceResult::query()->create([
                    'user_id' => $user->id,
                    'attempt_type' => RaceAttemptType::ClubTournament,
                    'race_id' => null,
                    'pvp_race_id' => null,
                    'club_tournament_id' => $tournament->id,
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

                $points = $this->scoringService->pointsForRace($won, $isTie, $playerScore, $opponentScore);

                $entry = ClubTournamentEntry::query()->create([
                    'club_tournament_id' => $tournament->id,
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'race_result_id' => $raceResult->id,
                    'points' => $points,
                    'counts_toward_club' => false,
                ]);

                $attempt->update([
                    'status' => RaceAttemptStatus::Succeeded,
                    'race_result_id' => $raceResult->id,
                    'error_code' => null,
                ]);

                $this->transactionService->record(
                    userId: $user->id,
                    type: TransactionType::ClubTournamentEntry,
                    currency: TransactionCurrency::PremiumFuel,
                    amount: -$entryCost,
                    balanceAfter: $profile->premium_fuel_current,
                    sourceType: $raceResult->getMorphClass(),
                    sourceId: $raceResult->id,
                );

                $this->scoringService->recalculateForUser($tournament, $user);

                return new ClubTournamentRaceStartResult(
                    raceResult: $raceResult->fresh(),
                    raceAttempt: $attempt->fresh(),
                    entry: $entry->fresh(),
                    replayed: false,
                );
            });
        } catch (RaceAttemptPendingException|RaceAttemptFailedException|IdempotencyKeyExpiredException $e) {
            throw $e;
        } catch (ValidationException $e) {
            $this->raceAttemptService->markPendingAttemptFailed(
                userId: $user->id,
                idempotencyKey: $idempotencyKey,
                attemptType: RaceAttemptType::ClubTournament,
                raceId: null,
                defenderUserId: null,
                errorCode: 'validation_failed',
                clubTournamentId: $tournament->id,
            );

            throw $e;
        }
    }

    /**
     * @return array{0: PlayerProfile, 1: Car, 2: ClubMember}
     */
    private function lockPlayerState(int $userId): array
    {
        $profile = PlayerProfile::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();

        $car = Car::query()
            ->whereKey($profile->active_car_id)
            ->lockForUpdate()
            ->firstOrFail();

        $membership = ClubMember::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();

        return [$profile, $car, $membership];
    }

    private function assertEligible(
        PlayerProfile $profile,
        Car $car,
        Club $club,
        ClubMember $membership,
        ClubTournament $tournament,
    ): void {
        $unlockLevel = (int) config('game.tournaments.unlock_level', 15);

        if ($profile->level < $unlockLevel) {
            throw ValidationException::withMessages([
                'level' => "Reach level {$unlockLevel} to enter club tournaments.",
            ]);
        }

        if ($membership->club_id !== $club->id) {
            throw ValidationException::withMessages([
                'club' => 'You must be a member of this club to race.',
            ]);
        }

        if ($car->user_id !== $profile->user_id) {
            throw ValidationException::withMessages([
                'active_car' => 'Select an active car before racing.',
            ]);
        }

        if (! $tournament->isActive()) {
            throw ValidationException::withMessages([
                'tournament' => 'No active tournament season.',
            ]);
        }

        if (now()->isAfter($tournament->ends_at)) {
            throw ValidationException::withMessages([
                'tournament' => 'This tournament season has ended.',
            ]);
        }

        $maxAttempts = (int) config('game.tournaments.max_attempts_per_player', 20);
        $attemptCount = ClubTournamentEntry::query()
            ->where('club_tournament_id', $tournament->id)
            ->where('user_id', $profile->user_id)
            ->count();

        if ($attemptCount >= $maxAttempts) {
            throw ValidationException::withMessages([
                'attempts' => 'You have reached the maximum tournament attempts for this season.',
            ]);
        }

        $entryCost = (int) config('game.premium_fuel.tournament_entry_cost', 1);

        if (! $this->premiumFuelService->hasEnough($profile, $entryCost)) {
            throw ValidationException::withMessages([
                'premium_fuel' => 'Not enough premium fuel for this tournament race.',
            ]);
        }
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
}
