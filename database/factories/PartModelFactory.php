<?php

namespace Database\Factories;

use App\Enums\CarClass;
use App\Enums\PartRarity;
use App\Enums\PartSlot;
use App\Models\PartModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartModel>
 */
class PartModelFactory extends Factory
{
    protected $model = PartModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slot' => fake()->randomElement(PartSlot::cases()),
            'rarity' => fake()->randomElement(PartRarity::cases()),
            'image_path' => null,
            'power_bonus' => fake()->numberBetween(0, 10),
            'acceleration_bonus' => fake()->numberBetween(0, 10),
            'grip_bonus' => fake()->numberBetween(0, 10),
            'handling_bonus' => fake()->numberBetween(0, 10),
            'price' => fake()->numberBetween(500, 5000),
            'unlock_level' => 5,
            'min_car_class' => CarClass::D,
            'active' => true,
        ];
    }
}
