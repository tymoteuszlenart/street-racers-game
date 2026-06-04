<?php

namespace Tests\Unit;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use App\Services\ConditionWearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionWearServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_part_at_zero_condition_is_soft_deleted_and_unequipped(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();

        $partModel = PartModel::factory()->create(['slot' => PartSlot::Engine]);

        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
            'condition_current' => 5,
            'condition_max' => 200,
        ]);

        app(ConditionWearService::class)->applyRaceWear($car, 10, 10);

        $this->assertDatabaseMissing('parts', [
            'id' => $part->id,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('parts', ['id' => $part->id]);
        $this->assertNull(Part::query()->find($part->id));
    }

    public function test_race_wear_damages_equipped_parts_and_car(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $carInitial = $car->condition_current;

        $partModel = PartModel::factory()->create(['slot' => PartSlot::Engine]);

        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
            'condition_current' => 200,
            'condition_max' => 200,
        ]);

        app(ConditionWearService::class)->applyRaceWear($car, 1, 1);

        $this->assertLessThan($carInitial, $car->fresh()->condition_current);
        $this->assertLessThan(200, $part->fresh()->condition_current);
    }
}
