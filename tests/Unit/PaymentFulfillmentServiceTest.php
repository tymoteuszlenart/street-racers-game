<?php

namespace Tests\Unit;

use App\Enums\FuelGrantMode;
use App\Enums\PaymentOrderStatus;
use App\Enums\ShopProductType;
use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFulfillmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fulfill_adds_regular_fuel_up_to_max(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 80, 'fuel_max' => 100]);

        $product = ShopProduct::factory()->create([
            'type' => ShopProductType::RegularFuel,
            'grant_mode' => FuelGrantMode::Add,
            'grant_amount' => 50,
        ]);

        $order = $this->pendingOrder($user, $product, 'cs_test_add');

        $service = app(PaymentFulfillmentService::class);
        $fulfilled = $service->fulfillFromStripeEvent(
            $this->completedPayload($order),
            'evt_test_add',
        );

        $profile->refresh();

        $this->assertTrue($fulfilled);
        $this->assertSame(100, $profile->fuel_current);
        $this->assertSame(['fuel' => 20], $order->fresh()->granted_payload);
    }

    public function test_fulfill_fill_to_max_refills_tank(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 25, 'fuel_max' => 100]);

        $product = ShopProduct::factory()->create([
            'type' => ShopProductType::RegularFuel,
            'grant_mode' => FuelGrantMode::FillToMax,
            'grant_amount' => null,
        ]);

        $order = $this->pendingOrder($user, $product, 'cs_test_fill');

        $service = app(PaymentFulfillmentService::class);
        $service->fulfillFromStripeEvent($this->completedPayload($order), 'evt_test_fill');

        $profile->refresh();

        $this->assertSame(100, $profile->fuel_current);
        $this->assertSame(['fuel' => 75], $order->fresh()->granted_payload);
    }

    public function test_fulfill_premium_truncates_at_paid_cap_and_raises_max(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update([
            'premium_fuel_current' => 18,
            'premium_fuel_max' => 5,
        ]);

        $product = ShopProduct::factory()->premiumFuel()->create([
            'grant_amount' => 10,
        ]);

        $order = $this->pendingOrder($user, $product, 'cs_test_premium');

        $service = app(PaymentFulfillmentService::class);
        $service->fulfillFromStripeEvent($this->completedPayload($order), 'evt_test_premium');

        $profile->refresh();
        $order->refresh();

        $this->assertSame(20, $profile->premium_fuel_max);
        $this->assertSame(20, $profile->premium_fuel_current);
        $this->assertSame(['premium_fuel' => 2], $order->granted_payload);
        $this->assertSame(PaymentOrderStatus::Paid, $order->status);
    }

    private function pendingOrder(User $user, ShopProduct $product, string $sessionId): PaymentOrder
    {
        return PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'amount_cents' => $product->price_cents,
            'provider_checkout_session_id' => $sessionId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function completedPayload(PaymentOrder $order): array
    {
        return [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $order->provider_checkout_session_id,
                    'client_reference_id' => $order->uuid,
                    'metadata' => [
                        'payment_order_uuid' => $order->uuid,
                    ],
                    'payment_status' => 'paid',
                ],
            ],
        ];
    }
}
