<?php

namespace App\Models;

use App\Enums\FuelGrantMode;
use App\Enums\ShopProductType;
use Database\Factories\ShopProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopProduct extends Model
{
    /** @use HasFactory<ShopProductFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'type',
        'name',
        'description',
        'price_cents',
        'stripe_price_id',
        'grant_mode',
        'grant_amount',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ShopProductType::class,
            'grant_mode' => FuelGrantMode::class,
            'active' => 'boolean',
        ];
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
