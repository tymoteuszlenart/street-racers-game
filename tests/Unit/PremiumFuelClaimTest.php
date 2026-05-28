<?php

namespace Tests\Unit;

use App\Enums\DailyRewardType;
use App\Enums\TransactionType;
use App\Models\DailyReward;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DailyRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PremiumFuelClaimTest extends TestCase
{
    use RefreshDatabase;

    private DailyRewardService $dailyRewardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dailyRewardService = app(DailyRewardService::class);
    }

    public function test_first_premium_claim_grants_fuel(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'premium_fuel_current' => 0,
            'premium_fuel_max' => 5,
        ]);

        $result = $this->dailyRewardService->claimPremium($user);

        $this->assertFalse($result->replayed);
        $this->assertSame(1, $result->premiumFuelGranted);
        $this->assertSame(1, $profile->fresh()->premium_fuel_current);
        $this->assertSame(1, DailyReward::query()->where('reward_type', DailyRewardType::Premium)->count());
        $this->assertTrue(
            Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', TransactionType::PremiumFuelClaim)
                ->exists(),
        );
    }

    public function test_duplicate_premium_claim_same_day_is_idempotent(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['premium_fuel_current' => 0, 'premium_fuel_max' => 5]);

        $first = $this->dailyRewardService->claimPremium($user);
        $second = $this->dailyRewardService->claimPremium($user);

        $this->assertFalse($first->replayed);
        $this->assertTrue($second->replayed);
        $this->assertSame(1, $profile->fresh()->premium_fuel_current);
        $this->assertSame(1, DailyReward::query()->where('reward_type', DailyRewardType::Premium)->count());
    }

    public function test_premium_claim_when_at_cap_is_rejected(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['premium_fuel_current' => 5, 'premium_fuel_max' => 5]);

        $this->expectException(ValidationException::class);

        try {
            $this->dailyRewardService->claimPremium($user);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('premium_fuel', $exception->errors());
            $this->assertSame(0, DailyReward::query()->count());
            throw $exception;
        }
    }
}
