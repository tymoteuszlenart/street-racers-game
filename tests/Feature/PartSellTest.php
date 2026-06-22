<?php

namespace Tests\Feature;

use App\Enums\PartAcquiredVia;
use App\Enums\TransactionType;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use App\Services\PartEquipService;
use App\Services\TuningShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartSellTest extends TestCase
{
    use RefreshDatabase;

    private function userWithInventoryPart(): array
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 5, 'cash' => 10_000]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $part = app(TuningShopService::class)->purchase($user, $partModel);

        return [$user, $part];
    }

    public function test_owner_can_sell_inventory_part(): void
    {
        [$user, $part] = $this->userWithInventoryPart();
        $cashBefore = $user->playerProfile()->firstOrFail()->cash;
        $partModel = $part->partModel;

        $response = $this->actingAs($user)->delete(route('garage.parts.sell', $part));

        $response->assertRedirect(route('garage.index').'#parts');
        $this->assertNull(Part::query()->find($part->id));
        $this->assertSame($cashBefore + (int) floor($partModel->price * 0.65), $user->playerProfile()->firstOrFail()->fresh()->cash);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::PartSale->value,
        ]);
    }

    public function test_cannot_sell_equipped_part(): void
    {
        [$user, $part] = $this->userWithInventoryPart();
        $partModel = $part->partModel;
        $carModel = CarModel::query()
            ->where('unlock_level', '>=', $partModel->unlock_level)
            ->orderBy('unlock_level')
            ->firstOrFail();
        $car = Car::query()->create([
            'user_id' => $user->id,
            'car_model_id' => $carModel->id,
            'acquired_via' => 'dealer',
        ]);
        app(PartEquipService::class)->equip($user, $part, $car);

        $response = $this->actingAs($user)->delete(route('garage.parts.sell', $part->fresh()));

        $response->assertSessionHasErrors('part');
        $this->assertNotNull(Part::query()->find($part->id));
    }

    public function test_cannot_sell_admin_part(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->for($user)->create([
            'acquired_via' => PartAcquiredVia::Admin,
            'purchase_price' => 1000,
            'car_id' => null,
        ]);

        $response = $this->actingAs($user)->delete(route('garage.parts.sell', $part));

        $response->assertSessionHasErrors('part');
    }

    public function test_other_user_cannot_sell_part(): void
    {
        [$user, $part] = $this->userWithInventoryPart();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->delete(route('garage.parts.sell', $part));

        $response->assertForbidden();
    }
}
