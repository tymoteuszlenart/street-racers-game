<?php

namespace App\Services;

use App\Enums\FuelGrantMode;
use App\Enums\PaymentOrderStatus;
use App\Enums\ShopProductType;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\PaymentOrder;
use App\Models\PlayerProfile;
use App\Models\ShopProduct;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PaymentFulfillmentService
{
    private const FULFILLMENT_EVENT_TYPES = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
    ];

    public function __construct(
        private readonly FuelService $fuelService,
        private readonly PremiumFuelService $premiumFuelService,
        private readonly TransactionService $transactionService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fulfillFromStripeEvent(array $payload, string $eventId): bool
    {
        $type = (string) ($payload['type'] ?? '');

        if ($type === 'checkout.session.expired') {
            $this->markSessionExpired($payload);

            return false;
        }

        if (! in_array($type, self::FULFILLMENT_EVENT_TYPES, true)) {
            return false;
        }

        /** @var array<string, mixed>|null $session */
        $session = $payload['data']['object'] ?? null;

        if (! is_array($session) || ! $this->isCheckoutSessionPaid($session)) {
            return false;
        }

        try {
            return DB::transaction(function () use ($session, $eventId): bool {
                $order = $this->findOrderForSession($session);

                if ($order === null) {
                    return false;
                }

                $order = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->first();

                if ($order === null) {
                    return false;
                }

                if ($order->isFulfilled() || $order->provider_event_id !== null) {
                    return false;
                }

                if (PaymentOrder::query()->where('provider_event_id', $eventId)->exists()) {
                    return false;
                }

                $profile = PlayerProfile::query()
                    ->where('user_id', $order->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $product = $order->shopProduct;

                if ($product === null) {
                    return false;
                }

                $grantedPayload = $this->grantProduct($profile, $product);

                $paymentIntentId = $session['payment_intent'] ?? null;
                if (is_string($paymentIntentId) && $paymentIntentId !== '') {
                    $order->provider_payment_intent_id = $paymentIntentId;
                }

                $order->status = PaymentOrderStatus::Paid;
                $order->provider_event_id = $eventId;
                $order->granted_payload = $grantedPayload;
                $order->fulfilled_at = now();
                $order->save();

                $this->recordTransaction($order, $product, $grantedPayload, $profile);

                return true;
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markSessionExpired(array $payload): void
    {
        /** @var array<string, mixed>|null $session */
        $session = $payload['data']['object'] ?? null;

        if (! is_array($session)) {
            return;
        }

        DB::transaction(function () use ($session): void {
            $order = $this->findOrderForSession($session);

            if ($order === null || $order->isFulfilled()) {
                return;
            }

            $order = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->first();

            if ($order === null || $order->isFulfilled() || $order->status !== PaymentOrderStatus::Pending) {
                return;
            }

            $order->status = PaymentOrderStatus::Cancelled;
            $order->save();
        });
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function findOrderForSession(array $session): ?PaymentOrder
    {
        $sessionId = $session['id'] ?? null;
        $orderUuid = $session['metadata']['payment_order_uuid'] ?? $session['client_reference_id'] ?? null;

        $hasSessionId = is_string($sessionId) && $sessionId !== '';
        $hasOrderUuid = is_string($orderUuid) && $orderUuid !== '';

        if (! $hasSessionId && ! $hasOrderUuid) {
            return null;
        }

        return PaymentOrder::query()
            ->where(function ($query) use ($sessionId, $orderUuid, $hasSessionId, $hasOrderUuid): void {
                if ($hasSessionId) {
                    $query->where('provider_checkout_session_id', $sessionId);
                }

                if ($hasOrderUuid) {
                    if ($hasSessionId) {
                        $query->orWhere('uuid', $orderUuid);
                    } else {
                        $query->where('uuid', $orderUuid);
                    }
                }
            })
            ->with('shopProduct')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function isCheckoutSessionPaid(array $session): bool
    {
        $paymentStatus = (string) ($session['payment_status'] ?? '');

        return in_array($paymentStatus, ['paid', 'no_payment_required'], true);
    }

    /**
     * @return array<string, int>
     */
    private function grantProduct(PlayerProfile $profile, ShopProduct $product): array
    {
        return match ($product->type) {
            ShopProductType::RegularFuel => $this->grantRegularFuel($profile, $product),
            ShopProductType::PremiumFuel => $this->grantPremiumFuel($profile, $product),
        };
    }

    /**
     * @return array<string, int>
     */
    private function grantRegularFuel(PlayerProfile $profile, ShopProduct $product): array
    {
        $granted = match ($product->grant_mode) {
            FuelGrantMode::FillToMax => $this->fuelService->fillToMax($profile),
            FuelGrantMode::Add, null => $this->fuelService->grant($profile, (int) ($product->grant_amount ?? 0)),
        };

        return ['fuel' => $granted];
    }

    /**
     * @return array<string, int>
     */
    private function grantPremiumFuel(PlayerProfile $profile, ShopProduct $product): array
    {
        $this->premiumFuelService->ensurePaidStorageCap($profile);
        $profile->refresh();

        $granted = $this->premiumFuelService->grantPurchase($profile, (int) ($product->grant_amount ?? 0));

        return ['premium_fuel' => $granted];
    }

    /**
     * @param  array<string, int>  $grantedPayload
     */
    private function recordTransaction(
        PaymentOrder $order,
        ShopProduct $product,
        array $grantedPayload,
        PlayerProfile $profile,
    ): void {
        if ($product->type === ShopProductType::RegularFuel) {
            $amount = $grantedPayload['fuel'] ?? 0;

            if ($amount <= 0) {
                return;
            }

            $this->transactionService->record(
                userId: $order->user_id,
                type: TransactionType::PaidFuelPurchase,
                currency: TransactionCurrency::Fuel,
                amount: $amount,
                balanceAfter: $profile->fuel_current,
                sourceType: PaymentOrder::class,
                sourceId: $order->id,
            );

            return;
        }

        $amount = $grantedPayload['premium_fuel'] ?? 0;

        if ($amount <= 0) {
            return;
        }

        $this->transactionService->record(
            userId: $order->user_id,
            type: TransactionType::PaidPremiumFuelPurchase,
            currency: TransactionCurrency::PremiumFuel,
            amount: $amount,
            balanceAfter: $profile->premium_fuel_current,
            sourceType: PaymentOrder::class,
            sourceId: $order->id,
        );
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        if (in_array($sqlState, ['23000', '23505'], true)) {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
