<?php

namespace Database\Factories;

use App\Enums\CarClass;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarModel>
 */
class CarModelFactory extends Factory
{
    protected $model = CarModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'class' => CarClass::D,
            'rarity' => 'common',
            'image_path' => null,
            'power' => fake()->numberBetween(40, 80),
            'acceleration' => fake()->numberBetween(40, 80),
            'weight' => fake()->numberBetween(40, 80),
            'grip' => fake()->numberBetween(40, 80),
            'handling' => fake()->numberBetween(40, 80),
            'durability' => fake()->numberBetween(40, 80),
            'upgrade_slots' => null,
            'price' => fake()->numberBetween(1000, 10000),
            'starter' => false,
            'unlock_level' => 1,
            'block_level' => fn (array $attributes) => ($attributes['unlock_level'] ?? 1) + 5,
            'active' => true,
        ];
    }

    public function starter(): static
    {
        return $this->state(fn () => [
            'class' => CarClass::D,
            'starter' => true,
            'unlock_level' => 1,
            'price' => 0,
        ]);
    }
}
