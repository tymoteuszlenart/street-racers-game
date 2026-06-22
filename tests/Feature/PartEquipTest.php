<?php

namespace Tests\Feature;

use App\Enums\AcquiredVia;
use App\Enums\CarClass;
use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use App\Services\PartEquipService;
use App\Services\TuningShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartEquipTest extends TestCase
{
    use RefreshDatabase;

    private function tuningReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 1]);

        return $user;
    }

    private function carForPart(User $user, PartModel $partModel): Car
    {
        $carModel = CarModel::query()
            ->where('unlock_level', '>=', $partModel->unlock_level)
            ->orderBy('unlock_level')
            ->firstOrFail();

        return Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carModel->id,
            'acquired_via' => AcquiredVia::Dealer,
        ]);
    }

    public function test_level_one_player_can_open_upgrades_page(): void
    {
        $user = $this->tuningReadyUser();
        $car = $user->cars()->firstOrFail();

        $this->actingAs($user)
            ->get(route('garage.upgrades', $car))
            ->assertOk()
            ->assertSee('Equipped slots', false);
    }

    public function test_level_one_player_can_buy_and_equip_shop_part(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['cash' => 10000]);
        $car = $user->cars()->firstOrFail();

        $partModel = PartModel::query()->where('name', 'Torque Four')->firstOrFail();
        app(TuningShopService::class)->purchase($user, $partModel);

        $part = Part::query()
            ->where('user_id', $user->id)
            ->where('part_model_id', $partModel->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('garage.show', $car))
            ->assertOk()
            ->assertSee(route('garage.upgrades', $car), false);

        $this->actingAs($user)
            ->post(route('garage.upgrades.equip', [$car, $part]))
            ->assertRedirect(route('garage.upgrades', $car));

        $this->assertSame($car->id, $part->fresh()->car_id);
    }

    public function test_player_can_equip_and_unequip_part(): void
    {
        $user = $this->tuningReadyUser();
        $car = $user->cars()->firstOrFail();

        $partModel = PartModel::query()->where('name', 'Torque Four')->firstOrFail();
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
        $user->playerProfile()->update(['level' => 10]);
        $secondModel = PartModel::query()->where('name', 'Semi-Slick Set')->firstOrFail();
        $car = $this->carForPart($user, $secondModel);

        $firstModel = PartModel::query()->where('name', 'All-Season Rubber')->firstOrFail();

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

    public function test_equip_rejects_unlock_level_too_low(): void
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
            ->from(route('garage.upgrades', $car))
            ->post(route('garage.upgrades.equip', [$car, $part]))
            ->assertRedirect(route('garage.upgrades', $car))
            ->assertSessionHasErrors('part');

        $this->assertNull($part->fresh()->car_id);
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

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $carA = $this->carForPart($user, $partModel);
        $carA->parts()->where('slot', PartSlot::Engine)->forceDelete();
        $carB = Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carA->car_model_id,
            'acquired_via' => AcquiredVia::Dealer,
        ]);
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

    public function test_unequip_rejects_stale_part_that_was_moved_to_another_car(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['cash' => 20000]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $carA = $this->carForPart($user, $partModel);
        $carA->parts()->where('slot', PartSlot::Engine)->forceDelete();
        $carB = Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carA->car_model_id,
            'acquired_via' => AcquiredVia::Dealer,
        ]);
        $stalePart = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $carA->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        Part::query()
            ->whereKey($stalePart->id)
            ->update(['car_id' => $carB->id]);

        $this->expectException(ValidationException::class);

        try {
            app(PartEquipService::class)->unequip($user, $stalePart, $carA);
        } finally {
            $this->assertSame($carB->id, $stalePart->fresh()->car_id);
        }
    }
}
