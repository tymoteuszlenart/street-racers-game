<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealer_index_excludes_starter_models(): void
    {
        $user = User::factory()->create();
        $starter = CarModel::query()->where('starter', true)->firstOrFail();

        $response = $this->actingAs($user)->get(route('shop.index'));

        $response->assertOk();
        $response->assertDontSee($starter->name);
    }

    public function test_dealer_index_hides_cars_above_player_reach(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 4]);
        $locked = CarModel::query()->where('name', 'Shogun Apex VIII')->firstOrFail();

        $response = $this->actingAs($user)->get(route('shop.index'));

        $response->assertOk();
        $response->assertDontSee($locked->name);
        $response->assertSee('Kurama Echo');
    }

    public function test_dealer_index_shows_cars_up_to_five_levels_above_player(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 1]);

        $response = $this->actingAs($user)->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('Kurama Echo');
        $response->assertDontSee('Veloce Astraea SV');
    }

    public function test_dealer_index_hides_cars_below_player_block_level(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);
        $outgrown = CarModel::query()->where('name', 'Kurama Echo')->firstOrFail();

        $response = $this->actingAs($user)->get(route('shop.index'));

        $response->assertOk();
        $response->assertDontSee($outgrown->name);
    }

    public function test_guest_cannot_view_dealer(): void
    {
        $response = $this->get(route('shop.index'));

        $response->assertRedirect(route('login'));
    }
}
