<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_active_car_for_registered_player(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $activeCar = $profile->activeCar;

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($activeCar->nickname);
        $response->assertSee($activeCar->carModel->name);
    }

    public function test_dashboard_shows_experience_progress_and_rankings_link(): void
    {
        config(['game.player.experience_per_level' => 100]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 1, 'experience' => 40]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('40 / 100 XP to level 2'), false)
            ->assertSee(__('View rankings'), false);
    }
}
