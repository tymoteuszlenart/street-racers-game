<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenCupEntry extends Model
{
    protected $fillable = [
        'open_cup_id',
        'user_id',
        'display_name',
        'car_snapshot',
        'solo_wins',
        'placement',
        'rewards_applied',
    ];

    protected function casts(): array
    {
        return [
            'car_snapshot' => 'array',
            'rewards_applied' => 'boolean',
        ];
    }

    public function openCup(): BelongsTo
    {
        return $this->belongsTo(OpenCup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
