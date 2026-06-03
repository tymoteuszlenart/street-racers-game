<?php

namespace Tests\Unit;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use App\Services\CarStatAggregator;
use App\Services\ConditionService;
use App\Services\RaceScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarStatAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregate_uses_base_model_stats_without_parts(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $car->update(['condition_current' => 80, 'condition_max' => 100]);
        $model = $car->carModel;

        $stats = app(CarStatAggregator::class)->aggregate($car);

        $this->assertSame($model->power, $stats['power']);
        $this->assertSame($model->acceleration, $stats['acceleration']);
        $this->assertSame($model->grip, $stats['grip']);
        $this->assertSame($model->handling, $stats['handling']);
        $this->assertEqualsWithDelta(80.0, $stats['condition_percent'], 0.01);
    }

    public function test_aggregate_sums_equipped_part_bonuses(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $model = $car->carModel;

        $partModel = PartModel::factory()->create([
            'slot' => PartSlot::Engine,
            'power_bonus' => 10,
            'acceleration_bonus' => 5,
            'grip_bonus' => 3,
            'handling_bonus' => 2,
        ]);

        Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $stats = app(CarStatAggregator::class)->aggregate($car->fresh());

        $this->assertSame($model->power + 10, $stats['power']);
        $this->assertSame($model->acceleration + 5, $stats['acceleration']);
        $this->assertSame($model->grip + 3, $stats['grip']);
        $this->assertSame($model->handling + 2, $stats['handling']);
    }

    public function test_aggregate_penalizes_overlevel_car_stats_until_player_reaches_unlock_level(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 1]);

        $model = CarModel::factory()->create([
            'power' => 100,
            'acceleration' => 90,
            'grip' => 80,
            'handling' => 70,
            'unlock_level' => 4,
            'block_level' => 9,
        ]);
        $car = Car::factory()
            ->for($user)
            ->for($model, 'carModel')
            ->create();

        $stats = app(CarStatAggregator::class)->aggregate($car->fresh());

        $this->assertSame(70, $stats['power']);
        $this->assertSame(63, $stats['acceleration']);
        $this->assertSame(56, $stats['grip']);
        $this->assertSame(49, $stats['handling']);
        $this->assertSame(30, $stats['level_penalty_percent']);

        $user->playerProfile()->update(['level' => 4]);

        $stats = app(CarStatAggregator::class)->aggregate($car->fresh());

        $this->assertSame(100, $stats['power']);
        $this->assertSame(90, $stats['acceleration']);
        $this->assertSame(80, $stats['grip']);
        $this->assertSame(70, $stats['handling']);
        $this->assertSame(0, $stats['level_penalty_percent']);
    }

    public function test_aggregate_scales_equipped_part_bonuses_by_upgrade_level(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $model = $car->carModel;

        $partModel = PartModel::factory()->create([
            'slot' => PartSlot::Engine,
            'power_bonus' => 10,
            'acceleration_bonus' => 0,
            'grip_bonus' => 0,
            'handling_bonus' => 0,
        ]);

        Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
            'upgrade_level' => 2,
        ]);

        $stats = app(CarStatAggregator::class)->aggregate($car->fresh());

        $this->assertSame($model->power + 12, $stats['power']);
    }

    public function test_worn_part_reduces_effective_stat_bonuses(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $model = $car->carModel;

        $partModel = PartModel::factory()->create([
            'slot' => PartSlot::Engine,
            'power_bonus' => 100,
            'acceleration_bonus' => 0,
            'grip_bonus' => 0,
            'handling_bonus' => 0,
        ]);

        Part::query()->create([
            'user_id' => $user->id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
            'condition_current' => 2,
            'condition_max' => 200,
        ]);

        $stats = app(CarStatAggregator::class)->aggregate($car->fresh());

        $factor = app(ConditionService::class)->partStatFactor(2, 200);
        $expectedBonus = (int) round(100 * $factor);

        $this->assertSame($model->power + $expectedBonus, $stats['power']);
        $this->assertLessThan($model->power + 100, $stats['power']);
    }

    public function test_condition_penalty_applies_in_score_calculator_not_aggregator(): void
    {
        $user = User::factory()->create();
        $car = $user->cars()->firstOrFail();
        $car->update(['condition_current' => 50, 'condition_max' => 100]);

        $stats = app(CarStatAggregator::class)->aggregate($car);
        $calculator = app(RaceScoreCalculator::class);

        $outcome = $calculator->calculate($stats, config('game.player.driver_stats.base'), 0.0);

        $this->assertEquals(5, $outcome['breakdown']['condition_penalty']);
        $this->assertEqualsWithDelta(50.0, $stats['condition_percent'], 0.01);
    }
}
