<?php

namespace App\Services;

use App\DTOs\DailyRewardClaimResult;
use App\DTOs\PremiumFuelClaimResult;
use App\Enums\DailyRewardType;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\DailyReward;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Support\GameDay;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyRewardService
{
    public function __construct(
        private readonly FuelService $fuelService,
        private readonly PremiumFuelService $premiumFuelService,
        private readonly TransactionService $transactionService,
    ) {}

    public function hasClaimedLoginToday(User $user): bool
    {
        return $this->findTodayLoginClaim($user->id) !== null;
    }

    public function canClaimLoginToday(User $user): bool
    {
        if ($this->hasClaimedLoginToday($user)) {
            return false;
        }

        $profile = $user->playerProfile;

        if ($profile === null) {
            return false;
        }

        $this->fuelService->regenerate($profile);
        $profile->refresh();

        return ! $this->fuelService->isTankFull($profile);
    }

    public function isLoginClaimBlockedByFullTank(User $user): bool
    {
        if ($this->hasClaimedLoginToday($user)) {
            return false;
        }

        $profile = $user->playerProfile;

        if ($profile === null) {
            return false;
        }

        $this->fuelService->regenerate($profile);
        $profile->refresh();

        return $this->fuelService->isTankFull($profile);
    }

    public function hasClaimedPremiumToday(User $user): bool
    {
        return $this->findTodayPremiumClaim($user->id) !== null;
    }

    public function canClaimPremiumToday(User $user): bool
    {
        if ($this->hasClaimedPremiumToday($user)) {
            return false;
        }

        $profile = $user->playerProfile;

        return $profile !== null && ! $this->premiumFuelService->isAtCap($profile);
    }

    public function isPremiumClaimBlockedByCap(User $user): bool
    {
        if ($this->hasClaimedPremiumToday($user)) {
            return false;
        }

        $profile = $user->playerProfile;

        return $profile !== null && $this->premiumFuelService->isAtCap($profile);
    }

    public function claimPremium(User $user): PremiumFuelClaimResult
    {
        try {
            return DB::transaction(function () use ($user) {
                $profile = PlayerProfile::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $claimDate = GameDay::today();

                $existing = $this->findTodayPremiumClaim($user->id);

                if ($existing !== null) {
                    return $this->premiumResultFromExisting($existing);
                }

                if ($this->premiumFuelService->isAtCap($profile)) {
                    throw ValidationException::withMessages([
                        'premium_fuel' => 'Your premium fuel storage is full.',
                    ]);
                }

                $configuredAmount = (int) config('game.premium_fuel.daily_claim_amount', 1);
                $granted = $this->premiumFuelService->grant($profile, $configuredAmount);

                $profile->premium_fuel_claimed_at = now();
                $profile->save();

                $dailyReward = DailyReward::query()->create([
                    'user_id' => $user->id,
                    'reward_type' => DailyRewardType::Premium,
                    'claim_date' => $claimDate->toDateString(),
                    'granted_payload' => ['premium_fuel' => $granted],
                    'created_at' => now(),
                ]);

                if ($granted > 0) {
                    $this->transactionService->record(
                        userId: $user->id,
                        type: TransactionType::PremiumFuelClaim,
                        currency: TransactionCurrency::PremiumFuel,
                        amount: $granted,
                        balanceAfter: $profile->premium_fuel_current,
                        sourceType: DailyReward::class,
                        sourceId: $dailyReward->id,
                    );
                }

                return new PremiumFuelClaimResult($dailyReward, false, $granted);
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findTodayPremiumClaim($user->id);

            if ($existing === null) {
                throw $exception;
            }

            return $this->premiumResultFromExisting($existing);
        }
    }

    public function claimLogin(User $user): DailyRewardClaimResult
    {
        try {
            return DB::transaction(function () use ($user) {
                $profile = PlayerProfile::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->fuelService->regenerate($profile);

                $claimDate = GameDay::today();

                $existing = $this->findTodayLoginClaim($user->id);

                if ($existing !== null) {
                    return $this->resultFromExisting($existing);
                }

                if ($this->fuelService->isTankFull($profile)) {
                    throw ValidationException::withMessages([
                        'fuel' => 'Your fuel tank is full. Spend some fuel before claiming the daily reward.',
                    ]);
                }

                $configuredFuel = (int) config('game.daily_rewards.login.fuel', 20);
                $fuelGranted = $this->fuelService->grant($profile, $configuredFuel);

                $dailyReward = DailyReward::query()->create([
                    'user_id' => $user->id,
                    'reward_type' => DailyRewardType::Login,
                    'claim_date' => $claimDate->toDateString(),
                    'granted_payload' => ['fuel' => $fuelGranted],
                    'created_at' => now(),
                ]);

                if ($fuelGranted > 0) {
                    $this->transactionService->record(
                        userId: $user->id,
                        type: TransactionType::DailyReward,
                        currency: TransactionCurrency::Fuel,
                        amount: $fuelGranted,
                        balanceAfter: $profile->fuel_current,
                        sourceType: DailyReward::class,
                        sourceId: $dailyReward->id,
                    );
                }

                return new DailyRewardClaimResult($dailyReward, false, $fuelGranted);
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findTodayLoginClaim($user->id);

            if ($existing === null) {
                throw $exception;
            }

            return $this->resultFromExisting($existing);
        }
    }

    private function findTodayPremiumClaim(int $userId): ?DailyReward
    {
        return DailyReward::query()
            ->where('user_id', $userId)
            ->where('reward_type', DailyRewardType::Premium->value)
            ->whereDate('claim_date', GameDay::today()->toDateString())
            ->first();
    }

    private function premiumResultFromExisting(DailyReward $dailyReward): PremiumFuelClaimResult
    {
        return new PremiumFuelClaimResult(
            $dailyReward,
            true,
            (int) ($dailyReward->granted_payload['premium_fuel'] ?? 0),
        );
    }

    private function findTodayLoginClaim(int $userId): ?DailyReward
    {
        return DailyReward::query()
            ->where('user_id', $userId)
            ->where('reward_type', DailyRewardType::Login->value)
            ->whereDate('claim_date', GameDay::today()->toDateString())
            ->first();
    }

    private function resultFromExisting(DailyReward $dailyReward): DailyRewardClaimResult
    {
        return new DailyRewardClaimResult(
            $dailyReward,
            true,
            (int) ($dailyReward->granted_payload['fuel'] ?? 0),
        );
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        if (in_array($sqlState, ['23000', '23505'], true)) {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
