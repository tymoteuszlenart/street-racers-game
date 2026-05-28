<?php

namespace App\Models;

use App\Enums\DailyRewardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReward extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'reward_type',
        'claim_date',
        'granted_payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_type' => DailyRewardType::class,
            'claim_date' => 'date',
            'granted_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
