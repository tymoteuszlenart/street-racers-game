<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class StarterCarTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_starter_car_and_sets_active_car_id(): void
    {
        $this->post('/register', [
            'name' => 'Racer One',
            'email' => 'racer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'racer@example.com')->firstOrFail();
        $profile = $user->playerProfile;

        $this->assertNotNull($profile);
        $this->assertNotNull($profile->active_car_id);

        $car = Car::query()->findOrFail($profile->active_car_id);
        $this->assertSame($user->id, $car->user_id);
        $this->assertSame('starter', $car->acquired_via->value);
        $this->assertNull($car->purchase_price);

        $starterModel = CarModel::query()->where('starter', true)->firstOrFail();
        $this->assertSame($starterModel->id, $car->car_model_id);
        $this->assertStringContainsString($starterModel->name, $car->nickname);
    }

    public function test_registration_fails_gracefully_when_starter_catalog_is_missing(): void
    {
        CarModel::query()->delete();

        $response = $this->post('/register', [
            'name' => 'Racer One',
            'email' => 'racer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'racer@example.com']);
        $this->assertDatabaseCount('player_profiles', 0);
    }

    public function test_profile_creation_rolls_back_when_starter_catalog_is_missing(): void
    {
        CarModel::query()->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Starter car catalog is not configured');

        try {
            DB::transaction(fn () => User::factory()->create());
        } catch (RuntimeException $e) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('player_profiles', 0);

            throw $e;
        }
    }
}
