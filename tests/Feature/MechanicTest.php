<?php

namespace Tests\Feature;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Enums\TransactionType;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use App\Services\MechanicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MechanicTest extends TestCase
{
    use RefreshDatabase;

    private function mechanicReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10, 'cash' => 50000]);

        return $user;
    }

    public function test_mechanic_index_forbidden_below_level_ten(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 9]);

        $this->actingAs($user)->get(route('mechanic.index'))->assertForbidden();
    }

    public function test_mechanic_index_available_at_level_ten(): void
    {
        $user = $this->mechanicReadyUser();

        $this->actingAs($user)->get(route('mechanic.index'))->assertOk();
    }

    public function test_part_upgrade_increases_level_and_costs_cash(): void
    {
        $user = $this->mechanicReadyUser();
        $partModel = PartModel::factory()->create(['price' => 1000]);
        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => $partModel->slot,
            'acquired_via' => PartAcquiredVia::Shop,
            'purchase_price' => 1000,
            'upgrade_level' => 0,
        ]);

        $cost = app(MechanicService::class)->upgradeCost($part);
        $expectedCash = $user->playerProfile->cash - $cost;

        $response = $this->actingAs($user)->post(route('mechanic.parts.upgrade', $part));

        $response->assertRedirect(route('mechanic.index', ['tab' => 'upgrade']));
        $this->assertSame(1, $part->fresh()->upgrade_level);
        $this->assertSame($expectedCash, $user->playerProfile->fresh()->cash);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::PartUpgrade->value,
            'amount' => -$cost,
        ]);
    }

    public function test_car_repair_restores_condition(): void
    {
        $user = $this->mechanicReadyUser();
        $car = $user->cars()->firstOrFail();
        $car->update(['condition_current' => 40, 'condition_max' => 100]);

        $cost = app(MechanicService::class)->repairCarCost($car);
        $expectedCash = $user->playerProfile->cash - $cost;

        $response = $this->actingAs($user)->post(route('mechanic.cars.repair', $car));

        $response->assertRedirect(route('mechanic.index', ['tab' => 'repair']));
        $this->assertSame(100, $car->fresh()->condition_current);
        $this->assertSame($expectedCash, $user->playerProfile->fresh()->cash);
    }

    public function test_part_repair_restores_condition(): void
    {
        $user = $this->mechanicReadyUser();
        $partModel = PartModel::factory()->create(['slot' => PartSlot::Engine]);
        $part = Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
            'condition_current' => 50,
            'condition_max' => 100,
        ]);

        $response = $this->actingAs($user)->post(route('mechanic.parts.repair', $part));

        $response->assertRedirect(route('mechanic.index', ['tab' => 'repair']));
        $this->assertSame(100, $part->fresh()->condition_current);
    }
}
