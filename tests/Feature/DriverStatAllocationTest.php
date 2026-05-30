<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverStatAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_allocate_unspent_stat_points(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'unspent_stat_points' => 3,
            'stat_power' => 1,
            'stat_acceleration' => 1,
            'stat_grip' => 1,
            'stat_handling' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('players.stats.store'), [
                'stat_power' => 2,
                'stat_acceleration' => 1,
                'stat_grip' => 0,
                'stat_handling' => 0,
            ])
            ->assertRedirect(route('players.show', $user))
            ->assertSessionHas('status', 'stats-allocated');

        $profile->refresh();
        $this->assertSame(0, $profile->unspent_stat_points);
        $this->assertSame(3, $profile->stat_power);
        $this->assertSame(2, $profile->stat_acceleration);
    }

    public function test_guest_cannot_allocate_stats(): void
    {
        $this->post(route('players.stats.store'), [
            'stat_power' => 1,
            'stat_acceleration' => 0,
            'stat_grip' => 0,
            'stat_handling' => 0,
        ])->assertRedirect(route('login'));
    }
}
