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
        $response->assertSee(__('Your rank: #:rank', ['rank' => '2']), false);
    }

    public function test_leaderboard_shows_global_rank_when_user_is_off_first_page(): void
    {
        $subject = User::factory()->create(['name' => 'Off Page Racer']);
        $subject->playerProfile()->firstOrFail()->update([
            'reputation' => 1,
            'level' => 1,
        ]);

        for ($index = 0; $index < 55; $index++) {
            $other = User::factory()->create();
            $other->playerProfile()->firstOrFail()->update([
                'reputation' => 1000 - $index,
                'level' => 1,
            ]);
        }

        $this->actingAs($subject)
            ->get(route('leaderboard.index'))
            ->assertOk()
            ->assertSee(__('Your rank: #:rank', ['rank' => '56']), false);
    }
}
