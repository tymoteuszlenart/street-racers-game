<?php

namespace Database\Factories;

use App\Enums\PaymentOrderStatus;
use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentOrder>
 */
class PaymentOrderFactory extends Factory
{
    protected $model = PaymentOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'shop_product_id' => ShopProduct::factory(),
            'status' => PaymentOrderStatus::Pending,
            'amount_cents' => 299,
            'provider_checkout_session_id' => 'cs_test_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'provider_payment_intent_id' => null,
            'provider_event_id' => null,
            'granted_payload' => null,
            'fulfilled_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => PaymentOrderStatus::Pending,
            'provider_event_id' => null,
            'granted_payload' => null,
            'fulfilled_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentOrderStatus::Paid,
            'provider_event_id' => 'evt_test_'.fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'granted_payload' => ['fuel' => 50],
            'fulfilled_at' => now(),
        ]);
    }
}
