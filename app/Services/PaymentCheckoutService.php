<?php

namespace App\Services;

use App\DTOs\CheckoutSessionResult;
use App\Enums\PaymentOrderStatus;
use App\Enums\ShopProductType;
use App\Exceptions\CheckoutRateLimitedException;
use App\Models\PaymentOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;

class PaymentCheckoutService
{
    public function __construct(
        private readonly StripeCheckoutGateway $stripeCheckoutGateway,
        private readonly PremiumFuelService $premiumFuelService,
    ) {}

    public static function checkoutRateLimitKey(int $userId): string
    {
        return 'shop-checkout:'.$userId;
    }

    public function createCheckoutSession(User $user, ShopProduct $product): CheckoutSessionResult
    {
        if (! $product->active) {
            abort(404);
        }

        $this->assertCheckoutNotRateLimited($user->id);

        $profile = $user->playerProfile;

        if ($profile === null) {
            throw ValidationException::withMessages([
                'shop' => 'Player profile is required to purchase from the shop.',
            ]);
        }

        if ($product->type === ShopProductType::PremiumFuel && ! $this->premiumFuelService->hasCapacity($profile)) {
            throw ValidationException::withMessages([
                'premium_fuel' => 'Your premium fuel storage is full.',
            ]);
        }

        $order = PaymentOrder::query()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'status' => PaymentOrderStatus::Pending,
            'amount_cents' => $product->price_cents,
        ]);

        $session = $this->stripeCheckoutGateway->createCheckoutSession(
            $this->buildSessionParams($order, $product),
        );

        $order->update([
            'provider_checkout_session_id' => $session->id,
        ]);

        $this->recordCheckoutRateLimitHit($user->id);

        $checkoutUrl = $session->url;

        if ($checkoutUrl === null || $checkoutUrl === '') {
            throw ValidationException::withMessages([
                'shop' => 'Unable to start checkout. Please try again.',
            ]);
        }

        return new CheckoutSessionResult($order->fresh(), $checkoutUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSessionParams(PaymentOrder $order, ShopProduct $product): array
    {
        $lineItem = $product->stripe_price_id !== null && $product->stripe_price_id !== ''
            ? ['price' => $product->stripe_price_id, 'quantity' => 1]
            : [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $product->price_cents,
                    'product_data' => array_filter([
                        'name' => $product->name,
                        'description' => $product->description,
                    ]),
                ],
                'quantity' => 1,
            ];

        return [
            'mode' => Session::MODE_PAYMENT,
            'client_reference_id' => $order->uuid,
            'metadata' => [
                'payment_order_uuid' => $order->uuid,
            ],
            'line_items' => [$lineItem],
            'success_url' => route('premium.success', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('premium.cancel', [], true),
        ];
    }

    private function assertCheckoutNotRateLimited(int $userId): void
    {
        $rateLimitKey = self::checkoutRateLimitKey($userId);
        $maxAttempts = (int) config('game.shop.checkout_rate_limit_per_minute', 10);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            throw new CheckoutRateLimitedException(
                retryAfterSeconds: max(1, RateLimiter::availableIn($rateLimitKey)),
            );
        }
    }

    private function recordCheckoutRateLimitHit(int $userId): void
    {
        RateLimiter::hit(self::checkoutRateLimitKey($userId), 60);
    }
}
