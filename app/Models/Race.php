<?php

namespace App\Models;

use Database\Factories\RaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    /** @use HasFactory<RaceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'unlock_level',
        'fuel_cost',
        'cash_reward_win',
        'cash_reward_loss',
        'reputation_reward_win',
        'reputation_reward_loss',
        'experience_reward_win',
        'experience_reward_loss',
        'opponent_power',
        'opponent_acceleration',
        'opponent_grip',
        'opponent_handling',
        'condition_damage_min',
        'condition_damage_max',
        'random_factor_variance',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'random_factor_variance' => 'float',
            'active' => 'boolean',
        ];
    }

    public function raceAttempts(): HasMany
    {
        return $this->hasMany(RaceAttempt::class);
    }

    public function raceResults(): HasMany
    {
        return $this->hasMany(RaceResult::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeUnlockedForLevel($query, int $level)
    {
        return $query->where('unlock_level', '<=', $level);
    }
}
