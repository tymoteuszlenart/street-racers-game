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
}
