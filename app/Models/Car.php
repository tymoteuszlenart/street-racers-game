<?php

namespace App\Models;

use App\Enums\AcquiredVia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends Model
{
    /** @use HasFactory<\Database\Factories\CarFactory> */
    use HasFactory;
    protected $fillable = [
        'user_id',
        'car_model_id',
        'nickname',
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
}
