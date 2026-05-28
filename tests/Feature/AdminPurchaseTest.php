<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_purchase_routes(): void
    {
        $user = User::factory()->create();
        $order = PaymentOrder::factory()->paid()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('admin.purchases.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.purchases.show', $order))->assertForbidden();
    }

    public function test_admin_sees_all_orders_on_index(): void
    {
        $admin = User::factory()->admin()->create();
        $player = User::factory()->create();
        $product = ShopProduct::factory()->create(['name' => 'Premium Boost']);

        PaymentOrder::factory()->paid()->create([
            'user_id' => $player->id,
            'shop_product_id' => $product->id,
            'granted_payload' => ['premium_fuel' => 5],
        ]);

        PaymentOrder::factory()->pending()->create([
            'user_id' => $admin->id,
            'shop_product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.purchases.index'));

        $response->assertOk();
        $response->assertSee($player->email);
        $response->assertSee($admin->email);
        $response->assertSee('Premium Boost');
    }

    public function test_admin_can_view_any_order_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $player = User::factory()->create(['email' => 'player@example.com']);
        $order = PaymentOrder::factory()->paid()->create([
            'user_id' => $player->id,
            'provider_checkout_session_id' => 'cs_test_admin_view',
            'provider_payment_intent_id' => 'pi_test_admin_view',
            'granted_payload' => ['fuel' => 25],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.purchases.show', $order));

        $response->assertOk();
        $response->assertSee('player@example.com');
        $response->assertSee('cs_test_admin_view');
        $response->assertSee('pi_test_admin_view');
        $response->assertSee('+25 regular fuel');
    }
}
