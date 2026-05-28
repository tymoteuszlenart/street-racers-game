<?php

namespace Tests\Feature;

use App\Enums\CarClass;
use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Car;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartEquipTest extends TestCase
{
    use RefreshDatabase;

    private function tuningReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 5]);

        return $user;
    }

    public function test_player_can_equip_and_unequip_part(): void
    {
        $user = $this->tuningReadyUser();
        $car = $user->cars()->firstOrFail();

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => $partModel->slot,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $this->actingAs($user)
            ->post(route('garage.upgrades.equip', [$car, $part]))
            ->assertRedirect(route('garage.upgrades', $car));

        $this->assertSame($car->id, $part->fresh()->car_id);

        $this->actingAs($user)
            ->delete(route('garage.upgrades.unequip', [$car, $part]))
            ->assertRedirect(route('garage.upgrades', $car));

        $this->assertNull($part->fresh()->car_id);
    }

    public function test_equip_swaps_incumbent_in_same_slot(): void
    {
        $user = $this->tuningReadyUser();
        $car = $user->cars()->firstOrFail();

        $firstModel = PartModel::query()->where('name', 'All-Season Rubber')->firstOrFail();
        $secondModel = PartModel::query()->where('name', 'Semi-Slick Set')->firstOrFail();

        $first = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $firstModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Tires,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $second = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $secondModel->id,
            'car_id' => null,
            'slot' => PartSlot::Tires,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $this->actingAs($user)
            ->post(route('garage.upgrades.equip', [$car, $second]))
            ->assertRedirect();

        $this->assertSame($car->id, $second->fresh()->car_id);
        $this->assertNull($first->fresh()->car_id);
    }

    public function test_equip_rejects_class_too_low(): void
    {
        $user = $this->tuningReadyUser();
        $car = $user->cars()->firstOrFail();

        $partModel = PartModel::factory()->create([
            'slot' => PartSlot::Engine,
            'min_car_class' => CarClass::B,
            'unlock_level' => 5,
        ]);

        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $this->actingAs($user)
            ->from(route('garage.upgrades', $car))
            ->post(route('garage.upgrades.equip', [$car, $part]))
            ->assertRedirect(route('garage.upgrades', $car))
            ->assertSessionHasErrors('part');

        $this->assertNull($part->fresh()->car_id);
    }

    public function test_equip_rejects_foreign_part(): void
    {
        $user = $this->tuningReadyUser();
        $other = User::factory()->create();
        $other->playerProfile()->update(['level' => 5]);

        $car = $user->cars()->firstOrFail();
        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();

        $part = Part::query()->create([
            'user_id' => $other->id,
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $this->actingAs($user)
            ->post(route('garage.upgrades.equip', [$car, $part]))
            ->assertForbidden();
    }

    public function test_equip_moves_part_from_another_owned_car(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['cash' => 20000]);

        $carA = $user->cars()->firstOrFail();
        $carB = Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carA->car_model_id,
            'nickname' => 'Second Ride',
            'acquired_via' => 'dealer',
        ]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $carA->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $this->actingAs($user)
            ->post(route('garage.upgrades.equip', [$carB, $part]))
            ->assertRedirect();

        $this->assertSame($carB->id, $part->fresh()->car_id);
    }
}
