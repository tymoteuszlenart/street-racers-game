<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCarTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_set_active_car(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $starterCar = Car::query()->where('user_id', $user->id)->firstOrFail();

        $secondCar = Car::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'Backup Ride',
        ]);

        $profile->update(['active_car_id' => $starterCar->id]);

        $response = $this->actingAs($user)->patch(route('garage.active', $secondCar));

        $response->assertRedirect(route('garage.show', $secondCar));
        $this->assertSame($secondCar->id, $profile->fresh()->active_car_id);
    }

    public function test_user_cannot_set_another_players_car_as_active(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $ownersCar = Car::query()->where('user_id', $owner->id)->firstOrFail();

        $response = $this->actingAs($intruder)->patch(route('garage.active', $ownersCar));

        $response->assertForbidden();
        $this->assertNotSame($ownersCar->id, $intruder->playerProfile->fresh()->active_car_id);
    }
}
