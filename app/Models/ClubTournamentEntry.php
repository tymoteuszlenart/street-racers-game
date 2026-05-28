<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTournamentEntry extends Model
{
    protected $fillable = [
        'club_tournament_id',
        'club_id',
        'user_id',
        'race_result_id',
        'points',
        'counts_toward_club',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'counts_toward_club' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(ClubTournament::class, 'club_tournament_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function raceResult(): BelongsTo
    {
        return $this->belongsTo(RaceResult::class);
    }
}
