<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_garage(): void
    {
        $response = $this->get(route('garage.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_view_garage_index(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();

        $response = $this->actingAs($user)->get(route('garage.index'));

        $response->assertOk();
        $response->assertSee($car->nickname);
    }

    public function test_user_cannot_view_another_players_car(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $ownersCar = Car::query()->where('user_id', $owner->id)->firstOrFail();

        $response = $this->actingAs($intruder)->get(route('garage.show', $ownersCar));

        $response->assertNotFound();
    }
}
