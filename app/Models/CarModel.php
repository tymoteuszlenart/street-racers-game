<?php

namespace App\Models;

use App\Enums\CarClass;
use App\Models\Concerns\ValidatesDealerPurchase;
use Database\Factories\CarModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    /** @use HasFactory<CarModelFactory> */
    use HasFactory;

    use ValidatesDealerPurchase;

    protected $fillable = [
        'name',
        'class',
        'rarity',
        'image_path',
        'power',
        'acceleration',
        'weight',
        'grip',
        'handling',
        'durability',
        'upgrade_slots',
        'price',
        'starter',
        'unlock_level',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'class' => CarClass::class,
            'upgrade_slots' => 'array',
            'starter' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeUnlockedForLevel($query, int $level)
    {
        return $query->where('unlock_level', '<=', $level);
    }

    public function scopeStarter($query)
    {
        return $query->where('starter', true);
    }

    public function scopeDealerCatalog($query)
    {
        return $query->where('starter', false);
    }
}
