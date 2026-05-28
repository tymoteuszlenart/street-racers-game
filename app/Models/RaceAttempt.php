<?php

namespace App\Models;

use App\Enums\RaceAttemptStatus;
use App\Enums\RaceAttemptType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'idempotency_key',
        'attempt_type',
        'race_id',
        'defender_user_id',
        'club_tournament_id',
        'status',
        'race_result_id',
        'error_code',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_type' => RaceAttemptType::class,
            'status' => RaceAttemptStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defender_user_id');
    }

    public function clubTournament(): BelongsTo
    {
        return $this->belongsTo(ClubTournament::class);
    }

    public function raceResult(): BelongsTo
    {
        return $this->belongsTo(RaceResult::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
