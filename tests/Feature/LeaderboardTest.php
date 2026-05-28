<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_leaderboard(): void
    {
        $this->get(route('leaderboard.index'))->assertRedirect(route('login'));
    }

    public function test_leaderboard_orders_by_reputation_then_level(): void
    {
        $low = User::factory()->create(['name' => 'Low Rep']);
        $low->playerProfile()->firstOrFail()->update([
            'reputation' => 10,
            'level' => 5,
        ]);

        $highLevel = User::factory()->create(['name' => 'Tied Rep High Level']);
        $highLevel->playerProfile()->firstOrFail()->update([
            'reputation' => 100,
            'level' => 10,
        ]);

        $leader = User::factory()->create(['name' => 'Top Racer']);
        $leader->playerProfile()->firstOrFail()->update([
            'reputation' => 100,
            'level' => 3,
        ]);

        $response = $this->actingAs($leader)->get(route('leaderboard.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Top Racer', 'Tied Rep High Level', 'Low Rep']);
        $response->assertSee('(you)', false);
    }
}
