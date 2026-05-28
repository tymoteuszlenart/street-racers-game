<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DailyRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyRewardClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_daily_rewards(): void
    {
        $this->get(route('daily-rewards.index'))->assertRedirect(route('login'));
        $this->post(route('daily-rewards.claim'))->assertRedirect(route('login'));
    }

    public function test_player_can_view_and_claim_daily_reward(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 40,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('daily-rewards.index'))
            ->assertOk()
            ->assertSee(__('Claim :fuel fuel', ['fuel' => 20]), false);

        $response = $this->actingAs($user)->post(route('daily-rewards.claim'));

        $response->assertRedirect(route('daily-rewards.index'));
        $response->assertSessionHas('status', 'daily-reward-claimed');
        $this->assertSame(60, $user->playerProfile()->firstOrFail()->fresh()->fuel_current);

        $this->actingAs($user)
            ->get(route('daily-rewards.index'))
            ->assertOk()
            ->assertSee(__('Come back after midnight to claim again.'), false);
    }

    public function test_dashboard_shows_daily_reward_cta_when_unclaimed_and_tank_not_full(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 50,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Claim daily reward'), false);
    }

    public function test_dashboard_shows_use_fuel_hint_when_tank_is_full(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Claim daily reward'), false)
            ->assertSee(__('Daily fuel waiting'), false);
    }

    public function test_dashboard_hides_daily_reward_cta_after_claim(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 50,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);
        app(DailyRewardService::class)->claimLogin($user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Claim daily reward'), false)
            ->assertDontSee(__('Daily fuel waiting'), false);
    }

    public function test_full_tank_shows_warning_and_blocks_claim(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_max' => 100,
            'fuel_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('daily-rewards.index'))
            ->assertOk()
            ->assertSee(__('Fuel tank is full'), false)
            ->assertSee(__('Tank full'), false);

        $response = $this->actingAs($user)->post(route('daily-rewards.claim'));

        $response->assertRedirect(route('daily-rewards.index'));
        $response->assertSessionHasErrors('fuel');
        $this->assertSame(100, $user->playerProfile()->firstOrFail()->fresh()->fuel_current);
    }
}
