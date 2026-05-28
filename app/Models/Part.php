<?php

namespace App\Models;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'part_model_id',
        'car_id',
        'slot',
        'acquired_via',
        'purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'slot' => PartSlot::class,
            'acquired_via' => PartAcquiredVia::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partModel(): BelongsTo
    {
        return $this->belongsTo(PartModel::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
