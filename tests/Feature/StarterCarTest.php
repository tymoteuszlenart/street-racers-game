<?php

namespace Tests\Feature;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Exceptions\StarterCarCatalogNotConfiguredException;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Part;
use App\Models\User;
use App\Services\StarterCarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $engine = Part::query()
            ->where('car_id', $car->id)
            ->where('slot', PartSlot::Engine)
            ->firstOrFail();
        $this->assertSame('starter', $engine->acquired_via->value);
        $this->assertSame('Stock Inline', $engine->partModel->name);

        $brakes = Part::query()
            ->where('car_id', $car->id)
            ->where('slot', PartSlot::Brakes)
            ->firstOrFail();
        $this->assertSame('starter', $brakes->acquired_via->value);
        $this->assertSame('OEM Discs', $brakes->partModel->name);
        $this->assertSame(PartAcquiredVia::Starter, $brakes->acquired_via);
    }

    public function test_registration_fails_when_starter_part_catalog_is_missing(): void
    {
        config(['game.starter_parts.engine' => 'Missing Engine']);

        $response = $this->post('/register', [
            'name' => 'Racer One',
            'email' => 'racer2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'racer2@example.com']);
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

    public function test_starter_service_repairs_missing_active_car_when_owned_cars_exist(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $ownedCar = $user->cars()->firstOrFail();

        $profile->setActiveCarId(null);

        $car = app(StarterCarService::class)->assignToProfile($profile);

        $this->assertSame($ownedCar->id, $car->id);
        $this->assertSame($ownedCar->id, $profile->fresh()->active_car_id);
        $this->assertSame(1, $user->cars()->count());
    }

    public function test_user_is_deleted_when_profile_setup_fails_without_outer_transaction(): void
    {
        CarModel::query()->delete();

        $this->expectException(StarterCarCatalogNotConfiguredException::class);

        try {
            User::factory()->create();
        } catch (StarterCarCatalogNotConfiguredException $e) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('player_profiles', 0);

            throw $e;
        }
    }

    public function test_profile_creation_rolls_back_when_starter_catalog_is_missing(): void
    {
        CarModel::query()->delete();

        $this->expectException(StarterCarCatalogNotConfiguredException::class);

        try {
            DB::transaction(fn () => User::factory()->create());
        } catch (StarterCarCatalogNotConfiguredException $e) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('player_profiles', 0);

            throw $e;
        }
    }
}
