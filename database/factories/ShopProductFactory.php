<?php

namespace Database\Factories;

use App\Enums\FuelGrantMode;
use App\Enums\ShopProductType;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProduct>
 */
class ShopProductFactory extends Factory
{
    protected $model = ShopProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(3),
            'type' => ShopProductType::RegularFuel,
            'name' => 'Fuel +50',
            'description' => 'Instant regular fuel refill.',
            'price_cents' => 299,
            'stripe_price_id' => null,
            'grant_mode' => FuelGrantMode::Add,
            'grant_amount' => 50,
            'sort_order' => 1,
            'active' => true,
        ];
    }

    public function premiumFuel(): static
    {
        return $this->state(fn () => [
            'type' => ShopProductType::PremiumFuel,
            'grant_mode' => null,
            'grant_amount' => 5,
            'name' => 'Premium Fuel Pack (5)',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }
}
