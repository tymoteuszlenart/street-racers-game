<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumFuelClaimTest extends TestCase
{
    use RefreshDatabase;

    private function tournamentReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update([
            'level' => 15,
            'premium_fuel_current' => 0,
            'premium_fuel_max' => 5,
        ]);

        return $user;
    }

    public function test_premium_fuel_page_forbidden_below_level_fifteen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('premium-fuel.index'))->assertForbidden();
    }

    public function test_player_can_claim_premium_fuel_via_http(): void
    {
        $user = $this->tournamentReadyUser();

        $response = $this->actingAs($user)->post(route('premium-fuel.claim'));

        $response->assertRedirect(route('premium-fuel.index'));
        $response->assertSessionHas('status', 'premium-fuel-claimed');
        $this->assertSame(1, $user->playerProfile->fresh()->premium_fuel_current);
    }
}
