<?php

namespace Database\Factories;

use App\Enums\AcquiredVia;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'car_model_id' => CarModel::factory(),
            'nickname' => fake()->unique()->words(2, true),
            'condition_current' => 100,
            'condition_max' => 100,
            'acquired_via' => AcquiredVia::Dealer,
            'purchase_price' => fake()->numberBetween(1000, 10000),
        ];
    }
}
