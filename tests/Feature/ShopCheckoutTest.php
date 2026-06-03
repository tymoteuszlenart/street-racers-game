<?php

namespace Tests\Feature;

use App\Enums\PaymentOrderStatus;
use App\Enums\ShopProductType;
use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\PaymentCheckoutService;
use App\Services\StripeCheckoutGateway;
use Database\Seeders\ShopProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Stripe\Checkout\Session;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShopProductSeeder::class);
        config([
            'services.stripe.secret' => 'sk_test_fake',
            'services.stripe.key' => 'pk_test_fake',
        ]);
    }

    public function test_checkout_creates_pending_order_and_redirects_to_stripe(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::query()->where('slug', 'fuel-add-50')->firstOrFail();

        RateLimiter::clear(PaymentCheckoutService::checkoutRateLimitKey($user->id));

        $this->mock(StripeCheckoutGateway::class, function ($mock) use ($user, $product): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->withArgs(function (array $params) use ($user, $product): bool {
                    $order = PaymentOrder::query()
                        ->where('user_id', $user->id)
                        ->latest('id')
                        ->first();

                    return $params['mode'] === Session::MODE_PAYMENT
                        && $params['client_reference_id'] === $order?->uuid
                        && ($params['metadata']['payment_order_uuid'] ?? null) === $order?->uuid
                        && isset($params['line_items'][0]['price_data']['unit_amount'])
                        && $params['line_items'][0]['price_data']['unit_amount'] === $product->price_cents;
                })
                ->andReturn(Session::constructFrom([
                    'id' => 'cs_test_checkout123',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_checkout123',
                ]));
        });

        $response = $this->actingAs($user)->post(route('premium.checkout', $product));

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_checkout123');

        $this->assertDatabaseHas('payment_orders', [
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'status' => PaymentOrderStatus::Pending->value,
            'amount_cents' => $product->price_cents,
            'provider_checkout_session_id' => 'cs_test_checkout123',
        ]);
    }

    public function test_inactive_product_returns_not_found(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::factory()->inactive()->create();

        $this->actingAs($user)
            ->post(route('premium.checkout', $product))
            ->assertNotFound();
    }

    public function test_premium_pack_at_cap_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update([
            'premium_fuel_current' => 20,
            'premium_fuel_max' => 20,
        ]);

        $product = ShopProduct::query()
            ->where('slug', 'premium-fuel-5')
            ->where('type', ShopProductType::PremiumFuel)
            ->firstOrFail();

        RateLimiter::clear(PaymentCheckoutService::checkoutRateLimitKey($user->id));

        $response = $this->actingAs($user)->post(route('premium.checkout', $product));

        $response->assertRedirect(route('premium.index'));
        $response->assertSessionHasErrors('premium_fuel');
        $this->assertDatabaseCount('payment_orders', 0);
    }

    public function test_premium_index_lists_active_products(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('premium.index'));

        $response->assertOk();
        $response->assertSee('Fuel +50');
        $response->assertSee('Premium Fuel (5)');
    }

    public function test_guest_cannot_access_premium(): void
    {
        $this->get(route('premium.index'))->assertRedirect(route('login'));
    }
}
