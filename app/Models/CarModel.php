<?php

namespace App\Models;

use App\Enums\CarClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    /** @use HasFactory<\Database\Factories\CarModelFactory> */
    use HasFactory;
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
}
