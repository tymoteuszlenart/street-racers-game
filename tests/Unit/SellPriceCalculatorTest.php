<?php

namespace Tests\Unit;

use App\Enums\AcquiredVia;
use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Car;
use App\Models\Part;
use App\Models\User;
use App\Services\SellPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private SellPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(SellPriceCalculator::class);
    }

    public function test_dealer_car_refund_uses_condition_and_standard_rate(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => 10_000,
            'condition_current' => 80,
            'condition_max' => 100,
        ]);

        $quote = $this->calculator->quoteCar($user, $car, includeEquippedParts: false);

        $this->assertTrue($quote->sellable);
        $this->assertSame(5200, $quote->lines[0]->refund);
        $this->assertSame(5200, $quote->total);
    }

    public function test_reward_car_uses_higher_refund_rate(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Reward,
            'purchase_price' => 10_000,
            'condition_current' => 80,
            'condition_max' => 100,
        ]);

        $quote = $this->calculator->quoteCar($user, $car, includeEquippedParts: false);

        $this->assertTrue($quote->sellable);
        $this->assertSame(6400, $quote->lines[0]->refund);
    }

    public function test_starter_car_sells_for_zero_when_not_active(): void
    {
        $user = User::factory()->create();
        Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => 5000,
        ]);

        $starter = $user->cars()->where('acquired_via', AcquiredVia::Starter)->firstOrFail();
        $dealerCar = $user->cars()->where('acquired_via', AcquiredVia::Dealer)->firstOrFail();
        $user->playerProfile()->update(['active_car_id' => $dealerCar->id]);

        $quote = $this->calculator->quoteCar($user, $starter);

        $this->assertTrue($quote->sellable);
        $this->assertSame(0, $quote->lines[0]->refund);
        $this->assertSame(0, $quote->total);
    }

    public function test_active_car_cannot_be_sold(): void
    {
        $user = User::factory()->create();
        Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => 5000,
        ]);

        $activeCar = $user->playerProfile()->firstOrFail()->active_car_id;
        $car = Car::query()->findOrFail($activeCar);

        $quote = $this->calculator->quoteCar($user, $car);

        $this->assertFalse($quote->sellable);
        $this->assertStringContainsString('active', strtolower($quote->blockedReason ?? ''));
    }

    public function test_admin_car_is_not_sellable(): void
    {
        $user = User::factory()->create();
        Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Admin,
            'purchase_price' => 5000,
        ]);

        $car = $user->cars()->where('acquired_via', AcquiredVia::Admin)->firstOrFail();

        $quote = $this->calculator->quoteCar($user, $car);

        $this->assertFalse($quote->sellable);
    }

    public function test_last_car_is_not_sellable(): void
    {
        $user = User::factory()->create();
        $user->cars()->where('acquired_via', AcquiredVia::Starter)->delete();

        $car = Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => 5000,
        ]);

        $quote = $this->calculator->quoteCar($user, $car);

        $this->assertFalse($quote->sellable);
        $this->assertStringContainsString('at least one car', $quote->blockedReason ?? '');
    }

    public function test_bundled_quote_includes_equipped_parts(): void
    {
        $user = User::factory()->create();
        Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => 5000,
        ]);

        $car = Car::factory()->for($user)->create([
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => 10_000,
            'condition_current' => 100,
            'condition_max' => 100,
        ]);

        Part::factory()->for($user)->create([
            'car_id' => $car->id,
            'purchase_price' => 2000,
            'acquired_via' => PartAcquiredVia::Shop,
            'condition_current' => 100,
            'condition_max' => 100,
        ]);

        $quote = $this->calculator->quoteCar($user, $car->fresh(['parts.partModel']));

        $this->assertTrue($quote->sellable);
        $this->assertCount(2, $quote->lines);
        $this->assertSame(7900, $quote->total);
    }

    public function test_equipped_part_cannot_be_sold_standalone(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $part = Part::factory()->for($user)->create([
            'car_id' => $car->id,
            'slot' => PartSlot::Turbo,
            'purchase_price' => 1000,
        ]);

        $quote = $this->calculator->quotePart($part);

        $this->assertFalse($quote->sellable);
    }

    public function test_admin_part_is_not_sellable(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->for($user)->create([
            'acquired_via' => PartAcquiredVia::Admin,
            'purchase_price' => 1000,
        ]);

        $quote = $this->calculator->quotePart($part);

        $this->assertFalse($quote->sellable);
    }

    public function test_starter_part_is_not_sellable(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->for($user)->create([
            'acquired_via' => PartAcquiredVia::Starter,
            'purchase_price' => null,
        ]);

        $quote = $this->calculator->quotePart($part);

        $this->assertFalse($quote->sellable);
    }

    public function test_inventory_part_refund(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->for($user)->create([
            'car_id' => null,
            'purchase_price' => 2000,
            'acquired_via' => PartAcquiredVia::Shop,
            'condition_current' => 50,
            'condition_max' => 100,
        ]);

        $quote = $this->calculator->quotePart($part);

        $this->assertTrue($quote->sellable);
        $this->assertSame(650, $quote->total);
    }
}
