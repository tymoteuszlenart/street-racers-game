<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_player_profile(): void
    {
        $user = User::factory()->create();

        $this->get(route('players.show', $user))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_another_players_profile(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create(['name' => 'Street Legend']);
        $target->playerProfile()->firstOrFail()->update([
            'level' => 7,
            'reputation' => 420,
            'stat_power' => 7,
            'stat_acceleration' => 7,
            'stat_grip' => 7,
            'stat_handling' => 7,
        ]);

        $this->actingAs($viewer)
            ->get(route('players.show', $target))
            ->assertOk()
            ->assertSee('Street Legend')
            ->assertSee(__('Driver stats'))
            ->assertSee(__('Reputation'), false)
            ->assertSee('420', false)
            ->assertSee(__('Force'), false)
            ->assertSee(__('Level'), false);
    }

    public function test_player_profile_shows_own_level_progress(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'level' => 2,
            'experience' => 350,
        ]);

        $this->actingAs($user)
            ->get(route('players.show', $user))
            ->assertOk()
            ->assertSee(__('(you)'), false)
            ->assertSee(__('150 / 450 XP to level 3'), false);
    }
}
