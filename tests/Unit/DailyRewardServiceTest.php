<?php

namespace Tests\Unit;

use App\Enums\TransactionType;
use App\Models\DailyReward;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DailyRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DailyRewardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DailyRewardService $dailyRewardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dailyRewardService = app(DailyRewardService::class);
    }

    public function test_first_login_claim_grants_fuel_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 50,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $result = $this->dailyRewardService->claimLogin($user);

        $this->assertFalse($result->replayed);
        $this->assertSame(20, $result->fuelGranted);
        $this->assertSame(70, $profile->fresh()->fuel_current);
        $this->assertSame(1, DailyReward::query()->count());
        $this->assertTrue(
            Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', TransactionType::DailyReward)
                ->exists(),
        );
    }

    public function test_duplicate_claim_same_day_is_idempotent(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 50,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $first = $this->dailyRewardService->claimLogin($user);
        $second = $this->dailyRewardService->claimLogin($user);

        $this->assertFalse($first->replayed);
        $this->assertTrue($second->replayed);
        $this->assertSame(20, $second->fuelGranted);
        $this->assertSame(70, $profile->fresh()->fuel_current);
        $this->assertSame(1, DailyReward::query()->count());
        $this->assertSame(1, Transaction::query()->where('type', TransactionType::DailyReward)->count());
    }

    public function test_claim_when_fuel_is_full_is_rejected(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 100,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->dailyRewardService->claimLogin($user);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('fuel', $exception->errors());
            $this->assertSame(0, DailyReward::query()->count());
            throw $exception;
        }
    }

    public function test_can_claim_is_false_when_tank_is_full(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $this->assertFalse($this->dailyRewardService->canClaimLoginToday($user));
        $this->assertTrue($this->dailyRewardService->isLoginClaimBlockedByFullTank($user));
    }

    public function test_claim_on_next_calendar_day_grants_again(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 80,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        config(['app.timezone' => 'UTC']);
        $this->travelTo('2026-05-28 12:00:00');
        $this->dailyRewardService->claimLogin($user);

        $this->travelTo('2026-05-29 01:00:00');
        $profile->update([
            'fuel_current' => 70,
            'fuel_updated_at' => now(),
        ]);
        $result = $this->dailyRewardService->claimLogin($user);

        $this->assertFalse($result->replayed);
        $this->assertSame(20, $result->fuelGranted);
        $this->assertSame(90, $profile->fresh()->fuel_current);
        $this->assertSame(2, DailyReward::query()->where('user_id', $user->id)->count());
    }
}
