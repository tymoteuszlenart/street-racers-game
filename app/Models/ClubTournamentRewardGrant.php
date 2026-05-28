<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTournamentRewardGrant extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'club_tournament_id',
        'user_id',
        'granted_payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(ClubTournament::class, 'club_tournament_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
