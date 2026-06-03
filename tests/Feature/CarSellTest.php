<?php

namespace Tests\Feature;

use App\Enums\AcquiredVia;
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

class CarSellTest extends TestCase
{
    use RefreshDatabase;

    private function userWithSecondDealerCar(): array
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 4, 'cash' => 100_000]);

        $carModel = CarModel::query()->where('name', 'Kurama Echo')->firstOrFail();
        $this->actingAs($user)->post(route('dealer.purchase', $carModel))->assertRedirect();

        $starter = $user->cars()->where('acquired_via', AcquiredVia::Starter)->firstOrFail();
        $dealerCar = $user->cars()->where('acquired_via', AcquiredVia::Dealer)->firstOrFail();

        return [$user, $starter, $dealerCar];
    }

    public function test_owner_can_sell_dealer_car(): void
    {
        [$user, $starter, $dealerCar] = $this->userWithSecondDealerCar();
        $cashBefore = $user->playerProfile()->firstOrFail()->cash;

        $response = $this->actingAs($user)->delete(route('garage.cars.sell', $dealerCar));

        $response->assertRedirect(route('garage.index'));
        $this->assertNull(Car::query()->find($dealerCar->id));
        $this->assertGreaterThan($cashBefore, $user->playerProfile()->firstOrFail()->fresh()->cash);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::CarSale->value,
        ]);
    }

    public function test_can_sell_starter_car_for_zero_when_not_active(): void
    {
        [$user, $starter, $dealerCar] = $this->userWithSecondDealerCar();
        $cashBefore = $user->playerProfile()->firstOrFail()->cash;
        $user->playerProfile()->update(['active_car_id' => $dealerCar->id]);

        $response = $this->actingAs($user)->delete(route('garage.cars.sell', $starter));

        $response->assertRedirect(route('garage.index'));
        $this->assertNull(Car::query()->find($starter->id));
        $this->assertSame($cashBefore, $user->playerProfile()->firstOrFail()->fresh()->cash);
    }

    public function test_cannot_sell_last_car(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();

        $response = $this->actingAs($user)->delete(route('garage.cars.sell', $car));

        $response->assertSessionHasErrors('car');
    }

    public function test_cannot_sell_active_car(): void
    {
        [$user, , $dealerCar] = $this->userWithSecondDealerCar();
        $user->playerProfile()->update(['active_car_id' => $dealerCar->id]);

        $response = $this->actingAs($user)->delete(route('garage.cars.sell', $dealerCar));

        $response->assertSessionHasErrors('car');
        $this->assertNotNull(Car::query()->find($dealerCar->id));
    }

    public function test_selling_car_with_equipped_parts_deletes_parts_and_credits_total(): void
    {
        [$user, , $dealerCar] = $this->userWithSecondDealerCar();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 5, 'cash' => 50_000]);
        $user->unsetRelation('playerProfile');

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $part = app(TuningShopService::class)->purchase($user, $partModel);
        app(PartEquipService::class)->equip($user, $part, $dealerCar);

        $cashBefore = $user->playerProfile()->firstOrFail()->fresh()->cash;

        $this->actingAs($user)->delete(route('garage.cars.sell', $dealerCar))->assertRedirect();

        $this->assertNull(Car::query()->find($dealerCar->id));
        $this->assertSame(0, Part::query()->where('user_id', $user->id)->count());
        $this->assertGreaterThan($cashBefore, $user->playerProfile()->firstOrFail()->fresh()->cash);
    }

    public function test_other_user_cannot_sell_car(): void
    {
        [$user, , $dealerCar] = $this->userWithSecondDealerCar();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->delete(route('garage.cars.sell', $dealerCar));

        $response->assertForbidden();
    }
}
