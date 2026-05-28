<?php

namespace App\Models;

use App\Enums\PaymentOrderStatus;
use Database\Factories\PaymentOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentOrder extends Model
{
    /** @use HasFactory<PaymentOrderFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (PaymentOrder $order): void {
            if ($order->uuid === null || $order->uuid === '') {
                $order->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'shop_product_id',
        'status',
        'amount_cents',
        'provider_checkout_session_id',
        'provider_payment_intent_id',
        'provider_event_id',
        'granted_payload',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentOrderStatus::class,
            'granted_payload' => 'array',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shopProduct(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class);
    }

    public function isFulfilled(): bool
    {
        return $this->fulfilled_at !== null;
    }
}
