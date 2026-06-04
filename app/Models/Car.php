<?php

namespace App\Models;

use App\Enums\AcquiredVia;
use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'car_model_id',
        'condition_current',
        'condition_max',
        'acquired_via',
        'purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'acquired_via' => AcquiredVia::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Car $car): void {
            if ($car->condition_max !== null) {
                return;
            }

            $max = CarModel::query()->find($car->car_model_id)?->durability
                ?? (int) config('game.condition.car_max', 999);

            $car->condition_max = $max;
            $car->condition_current ??= $max;
        });
    }
}
