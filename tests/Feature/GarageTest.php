<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_players_car(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $ownersCar = Car::query()->where('user_id', $owner->id)->firstOrFail();

        $response = $this->actingAs($intruder)->get(route('garage.show', $ownersCar));

        $response->assertForbidden();
    }
}
