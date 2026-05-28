<?php

namespace App\Services;

use App\DTOs\DailyRewardClaimResult;
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
