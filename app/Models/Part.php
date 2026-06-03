<?php

namespace App\Models;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'part_model_id',
        'car_id',
        'slot',
        'acquired_via',
        'purchase_price',
        'condition_current',
        'condition_max',
        'upgrade_level',
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

    protected static function booted(): void
    {
        static::creating(function (Part $part): void {
            if ($part->condition_max !== null) {
                return;
            }

            $max = (int) config('game.condition.part_max', 200);

            $part->condition_max = $max;
            $part->condition_current ??= $max;
        });
    }
}
