<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealer_purchase_rejects_insufficient_cash(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['cash' => 100]);

        $carModel = CarModel::query()->where('name', 'Neon Hatch')->firstOrFail();

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Dream Car',
        ]);

        $response->assertSessionHasErrors('cash');
        $this->assertSame(100, $profile->fresh()->cash);
        $this->assertSame(1, Car::query()->where('user_id', $user->id)->count());
    }

    public function test_dealer_purchase_rejects_starter_model(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $initialCash = $profile->cash;

        $carModel = CarModel::query()->where('starter', true)->firstOrFail();

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Extra Starter',
        ]);

        $response->assertSessionHasErrors('car_model');
        $this->assertSame($initialCash, $profile->fresh()->cash);
        $this->assertSame(1, Car::query()->where('user_id', $user->id)->count());
    }

    public function test_dealer_purchase_rejects_level_too_low(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['cash' => 100000]);

        $carModel = CarModel::query()->where('name', 'Voltage GT')->firstOrFail();

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Dream Car',
        ]);

        $response->assertSessionHasErrors('car_model');
        $this->assertSame(1, Car::query()->where('user_id', $user->id)->count());
    }

    public function test_dealer_purchase_creates_car_and_deducts_cash(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $starterCarId = $profile->active_car_id;

        $carModel = CarModel::query()->where('name', 'Neon Hatch')->firstOrFail();
        $expectedCash = $profile->cash - $carModel->price;

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Street Legend',
        ]);

        $response->assertRedirect();
        $profile->refresh();

        $this->assertSame($expectedCash, $profile->cash);
        $this->assertSame($starterCarId, $profile->active_car_id);

        $purchased = Car::query()
            ->where('user_id', $user->id)
            ->where('nickname', 'Street Legend')
            ->first();

        $this->assertNotNull($purchased);
        $this->assertSame($carModel->id, $purchased->car_model_id);
        $this->assertSame('dealer', $purchased->acquired_via->value);
        $this->assertSame($carModel->price, $purchased->purchase_price);
    }

    public function test_dealer_purchase_sets_active_car_when_player_has_none(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->setActiveCarId(null);
        $profile->update(['cash' => 20000]);

        $carModel = CarModel::query()->where('name', 'Neon Hatch')->firstOrFail();

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'First Ride',
        ]);

        $response->assertRedirect();
        $profile->refresh();

        $purchased = Car::query()
            ->where('user_id', $user->id)
            ->where('nickname', 'First Ride')
            ->firstOrFail();

        $this->assertSame($purchased->id, $profile->active_car_id);
    }

    public function test_dealer_purchase_rejects_inactive_model(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $initialCash = $profile->cash;

        $carModel = CarModel::factory()->create([
            'name' => 'Retired Racer',
            'active' => false,
            'starter' => false,
            'unlock_level' => 1,
            'price' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Should Fail',
        ]);

        $response->assertSessionHasErrors('car_model');
        $this->assertSame($initialCash, $profile->fresh()->cash);
        $this->assertSame(1, Car::query()->where('user_id', $user->id)->count());
    }

    public function test_dealer_purchase_rejects_whitespace_nickname(): void
    {
        $user = User::factory()->create();

        $carModel = CarModel::query()->where('name', 'Neon Hatch')->firstOrFail();

        $response = $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => '   ',
        ]);

        $response->assertSessionHasErrors('nickname');
    }

    public function test_player_can_buy_same_model_multiple_times(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['cash' => 20000]);

        $carModel = CarModel::query()->where('name', 'Neon Hatch')->firstOrFail();

        $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Copy One',
        ]);

        $this->actingAs($user)->post(route('dealer.purchase', $carModel), [
            'nickname' => 'Copy Two',
        ]);

        $this->assertSame(3, Car::query()->where('user_id', $user->id)->count());
    }
}
