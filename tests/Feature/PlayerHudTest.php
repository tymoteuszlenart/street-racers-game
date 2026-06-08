<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlayerLevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerHudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_show_player_hud_with_stats_and_xp(): void
    {
        $user = User::factory()->create(['name' => 'Street King']);
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'level' => 1,
            'experience' => 40,
            'cash' => 12_500,
            'cups' => 7,
            'fuel_current' => 45,
            'fuel_max' => 100,
            'premium_fuel_current' => 2,
            'premium_fuel_max' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-testid="player-hud"', false)
            ->assertSee('Street King', false)
            ->assertSee('$12,500', false)
            ->assertSee('7', false)
            ->assertSee('45/100', false)
            ->assertSee('2/5', false)
            ->assertSee(__('Lvl').' 1', false)
            ->assertSee('40 / 200 '.__('XP'), false);
    }

    public function test_player_hud_shows_max_level_state(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $levelService = app(PlayerLevelService::class);
        $profile->update([
            'level' => $levelService->maxLevel(),
            'experience' => $levelService->maxExperience(),
        ]);

        $this->actingAs($user)
            ->get(route('garage.index'))
            ->assertOk()
            ->assertSee('data-testid="player-hud"', false)
            ->assertSee(__('Max level'), false);
    }

    public function test_guest_pages_do_not_show_player_hud(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-testid="player-hud"', false);
    }
}
