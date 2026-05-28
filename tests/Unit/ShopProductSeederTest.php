<?php

namespace Tests\Unit;

use App\Models\ShopProduct;
use Database\Seeders\ShopProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_four_active_products_from_config(): void
    {
        $this->seed(ShopProductSeeder::class);

        $this->assertCount(4, ShopProduct::query()->where('active', true)->get());
        $this->assertSame(
            ['fuel-add-50', 'fuel-fill-max', 'premium-fuel-5', 'premium-fuel-10'],
            ShopProduct::query()->orderBy('sort_order')->pluck('slug')->all(),
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ShopProductSeeder::class);
        $this->seed(ShopProductSeeder::class);

        $this->assertCount(4, ShopProduct::query()->get());
    }
}
