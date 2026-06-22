<?php

namespace Tests\Feature;

use App\Enums\PartAcquiredVia;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use App\Services\PartEquipService;
use App\Services\TuningShopService;
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
        $response->assertSee($car->carModel->name);
        $response->assertSee(asset('garage.png'), false);
    }

    public function test_garage_index_lists_owned_parts_by_slot(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 5, 'cash' => 10000]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        app(TuningShopService::class)->purchase($user, $partModel);

        $response = $this->actingAs($user)->get(route('garage.index'));

        $response->assertOk();
        $response->assertSee('Parts', false);
        $response->assertSee($partModel->name, false);
        $response->assertSee('In inventory', false);
        $response->assertSee('Show equipped parts', false);
    }

    public function test_garage_parts_tab_shows_equipped_parts_toggle(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 5]);
        $user->cars()->delete();
        Part::query()->where('user_id', $user->id)->delete();

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $engineModel = PartModel::query()->where('name', 'Stock Inline')->firstOrFail();
        $brakesModel = PartModel::query()->where('name', 'OEM Discs')->firstOrFail();
        $carModel = CarModel::query()
            ->where('unlock_level', '>=', $partModel->unlock_level)
            ->orderBy('unlock_level')
            ->firstOrFail();
        $car = Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carModel->id,
            'acquired_via' => 'dealer',
        ]);

        Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $brakesModel->id,
            'car_id' => $car->id,
            'slot' => $brakesModel->slot,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $engineModel->id,
            'car_id' => null,
            'slot' => $engineModel->slot,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => $partModel->slot,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        app(PartEquipService::class)->equip($user, $part, $car);

        $response = $this->actingAs($user)->get(route('garage.index'));

        $response->assertOk();
        $response->assertSee('Show equipped parts', false);
        $response->assertSee('2 equipped', false);
        $response->assertSee('1 spare', false);
        $response->assertSee($partModel->name, false);
        $response->assertSee('Equipped on', false);
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
