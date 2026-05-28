<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_purchase_history(): void
    {
        $this->get(route('purchases.index'))->assertRedirect(route('login'));
    }

    public function test_player_sees_own_orders_only_newest_first(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = ShopProduct::factory()->create(['name' => 'Fuel Pack 50']);

        $older = PaymentOrder::factory()->paid()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'amount_cents' => 299,
            'granted_payload' => ['fuel' => 50],
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $newer = PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'amount_cents' => 499,
            'created_at' => now(),
        ]);

        PaymentOrder::factory()->paid()->create([
            'user_id' => $other->id,
            'shop_product_id' => $product->id,
            'amount_cents' => 999,
            'granted_payload' => ['fuel' => 99],
        ]);

        $response = $this->actingAs($user)->get(route('purchases.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['$4.99', '$2.99']);
        $response->assertSee('Fuel Pack 50');
        $response->assertSee('+50 regular fuel');
        $response->assertSee('pending');
        $response->assertSee('paid');
        $response->assertDontSee('$9.99');
        $response->assertDontSee('+99 regular fuel');
    }
}
