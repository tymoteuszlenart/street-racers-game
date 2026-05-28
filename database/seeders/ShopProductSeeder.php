<?php

namespace Database\Seeders;

use App\Enums\FuelGrantMode;
use App\Enums\ShopProductType;
use App\Models\ShopProduct;
use Illuminate\Database\Seeder;

class ShopProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('game.shop.products', []) as $product) {
            $type = ShopProductType::from($product['type']);
            $grantMode = isset($product['grant_mode'])
                ? FuelGrantMode::from($product['grant_mode'])
                : null;

            ShopProduct::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    'type' => $type,
                    'name' => $product['name'],
                    'description' => $product['description'] ?? null,
                    'price_cents' => $product['price_cents'],
                    'stripe_price_id' => $product['stripe_price_id'] ?? null,
                    'grant_mode' => $grantMode,
                    'grant_amount' => $product['grant_amount'] ?? null,
                    'sort_order' => $product['sort_order'] ?? 0,
                    'active' => $product['active'] ?? true,
                ],
            );
        }
    }
}
