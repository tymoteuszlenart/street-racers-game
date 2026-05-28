<?php

namespace Tests\Feature;

use App\Enums\PaymentOrderStatus;
use App\Enums\TransactionType;
use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ShopProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShopProductSeeder::class);
        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    public function test_valid_signature_grants_fuel_once_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 10, 'fuel_max' => 100]);

        $product = ShopProduct::query()->where('slug', 'fuel-add-50')->firstOrFail();
        $order = PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'amount_cents' => $product->price_cents,
            'provider_checkout_session_id' => 'cs_test_webhook_grant',
        ]);

        $payload = $this->checkoutSessionCompletedPayload($order, paymentStatus: 'paid');
        $eventId = 'evt_test_grant_once';

        $response = $this->postSignedWebhook($payload, $eventId);

        $response->assertOk();

        $order->refresh();
        $profile->refresh();

        $this->assertSame(PaymentOrderStatus::Paid, $order->status);
        $this->assertSame($eventId, $order->provider_event_id);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertSame(['fuel' => 50], $order->granted_payload);
        $this->assertSame(60, $profile->fuel_current);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::PaidFuelPurchase->value,
            'amount' => 50,
            'balance_after' => 60,
            'source_type' => PaymentOrder::class,
            'source_id' => $order->id,
        ]);
    }

    public function test_replay_same_event_id_does_not_double_grant(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 10, 'fuel_max' => 100]);

        $product = ShopProduct::query()->where('slug', 'fuel-add-50')->firstOrFail();
        $order = PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'amount_cents' => $product->price_cents,
            'provider_checkout_session_id' => 'cs_test_webhook_replay',
        ]);

        $payload = $this->checkoutSessionCompletedPayload($order, paymentStatus: 'paid');
        $eventId = 'evt_test_replay';

        $this->postSignedWebhook($payload, $eventId)->assertOk();
        $this->postSignedWebhook($payload, $eventId)->assertOk();

        $profile->refresh();

        $this->assertSame(60, $profile->fuel_current);
        $this->assertSame(1, Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::PaidFuelPurchase->value)
            ->count());
    }

    public function test_invalid_signature_returns_400_without_grant(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 10, 'fuel_max' => 100]);

        $product = ShopProduct::query()->where('slug', 'fuel-add-50')->firstOrFail();
        $order = PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'provider_checkout_session_id' => 'cs_test_invalid_sig',
        ]);

        $payload = json_encode($this->checkoutSessionCompletedPayload($order, paymentStatus: 'paid'), JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => 'invalid',
            ],
            $payload,
        );

        $response->assertStatus(400);

        $profile->refresh();
        $order->refresh();

        $this->assertSame(10, $profile->fuel_current);
        $this->assertSame(PaymentOrderStatus::Pending, $order->status);
        $this->assertNull($order->fulfilled_at);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_unpaid_completed_session_does_not_grant(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 10, 'fuel_max' => 100]);

        $product = ShopProduct::query()->where('slug', 'fuel-add-50')->firstOrFail();
        $order = PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'provider_checkout_session_id' => 'cs_test_unpaid',
        ]);

        $payload = $this->checkoutSessionCompletedPayload($order, paymentStatus: 'unpaid');

        $this->postSignedWebhook($payload, 'evt_test_unpaid')->assertOk();

        $profile->refresh();
        $order->refresh();

        $this->assertSame(10, $profile->fuel_current);
        $this->assertSame(PaymentOrderStatus::Pending, $order->status);
        $this->assertNull($order->fulfilled_at);
    }

    public function test_expired_session_does_not_grant(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update(['fuel_current' => 10, 'fuel_max' => 100]);

        $product = ShopProduct::query()->where('slug', 'fuel-add-50')->firstOrFail();
        $order = PaymentOrder::factory()->pending()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'provider_checkout_session_id' => 'cs_test_expired',
        ]);

        $payload = [
            'id' => 'evt_test_expired',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => $order->provider_checkout_session_id,
                    'client_reference_id' => $order->uuid,
                    'metadata' => [
                        'payment_order_uuid' => $order->uuid,
                    ],
                    'payment_status' => 'unpaid',
                ],
            ],
        ];

        $this->postSignedWebhook($payload, 'evt_test_expired')->assertOk();

        $profile->refresh();
        $order->refresh();

        $this->assertSame(10, $profile->fuel_current);
        $this->assertSame(PaymentOrderStatus::Cancelled, $order->status);
        $this->assertNull($order->fulfilled_at);
        $this->assertDatabaseCount('transactions', 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSignedWebhook(array $payload, string $eventId): TestResponse
    {
        $payload['id'] = $eventId;
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->signWebhookPayload($body);

        return $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => $signature,
            ],
            $body,
        );
    }

    private function signWebhookPayload(string $body): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::WEBHOOK_SECRET);

        return "t={$timestamp},v1={$signature}";
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutSessionCompletedPayload(
        PaymentOrder $order,
        string $paymentStatus,
    ): array {
        return [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $order->provider_checkout_session_id,
                    'client_reference_id' => $order->uuid,
                    'metadata' => [
                        'payment_order_uuid' => $order->uuid,
                    ],
                    'payment_status' => $paymentStatus,
                    'payment_intent' => 'pi_test_123',
                ],
            ],
        ];
    }
}
