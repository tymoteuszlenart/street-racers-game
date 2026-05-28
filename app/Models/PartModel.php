<?php

namespace App\Models;

use App\Enums\CarClass;
use App\Enums\PartRarity;
use App\Enums\PartSlot;
use App\Models\Concerns\ValidatesTuningPurchase;
use Database\Factories\PartModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartModel extends Model
{
    /** @use HasFactory<PartModelFactory> */
    use HasFactory;

    use ValidatesTuningPurchase;

    protected $fillable = [
        'name',
        'slot',
        'rarity',
        'image_path',
        'power_bonus',
        'acceleration_bonus',
        'grip_bonus',
        'handling_bonus',
        'price',
        'unlock_level',
        'min_car_class',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'slot' => PartSlot::class,
            'rarity' => PartRarity::class,
            'min_car_class' => CarClass::class,
            'active' => 'boolean',
        ];
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeShopCatalog($query, int $playerLevel)
    {
        return $query
            ->active()
            ->where('unlock_level', '<=', $playerLevel)
            ->orderBy('slot')
            ->orderBy('unlock_level')
            ->orderBy('price');
    }
}
