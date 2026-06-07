<?php

namespace App\Models;

use App\Enums\RaceAttemptType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RaceResult extends Model
{
    protected $fillable = [
        'user_id',
        'attempt_type',
        'race_id',
        'pvp_race_id',
        'club_tournament_id',
        'open_cup_id',
        'won',
        'is_tie',
        'player_score',
        'opponent_score',
        'score_breakdown',
        'random_factor',
    ];

    protected function casts(): array
    {
        return [
            'attempt_type' => RaceAttemptType::class,
            'won' => 'boolean',
            'is_tie' => 'boolean',
            'player_score' => 'float',
            'opponent_score' => 'float',
            'random_factor' => 'float',
            'score_breakdown' => 'array',
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

    public function pvpRace(): BelongsTo
    {
        return $this->belongsTo(PvpRace::class);
    }

    public function clubTournament(): BelongsTo
    {
        return $this->belongsTo(ClubTournament::class);
    }

    public function openCup(): BelongsTo
    {
        return $this->belongsTo(OpenCup::class);
    }

    public function raceAttempt(): HasOne
    {
        return $this->hasOne(RaceAttempt::class);
    }
}
