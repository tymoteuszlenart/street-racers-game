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

        $response = $this->actingAs($user)->get(route('dealer.index'));

        $response->assertOk();
        $response->assertDontSee($starter->name);
    }

    public function test_dealer_index_hides_cars_above_player_level(): void
    {
        $user = User::factory()->create();
        $locked = CarModel::query()->where('name', 'Voltage GT')->firstOrFail();

        $response = $this->actingAs($user)->get(route('dealer.index'));

        $response->assertOk();
        $response->assertDontSee($locked->name);
        $response->assertSee('Neon Hatch');
    }

    public function test_guest_cannot_view_dealer(): void
    {
        $response = $this->get(route('dealer.index'));

        $response->assertRedirect(route('login'));
    }
}
