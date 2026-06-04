<?php

namespace App\Models;

use App\Enums\RaceTier;
use App\Enums\RaceType;
use Database\Factories\RaceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    /** @use HasFactory<RaceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'race_type',
        'race_tier',
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
        'opponent_stat_power',
        'opponent_stat_acceleration',
        'opponent_stat_grip',
        'opponent_stat_handling',
        'condition_damage_min',
        'condition_damage_max',
        'random_factor_variance',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'race_type' => RaceType::class,
            'race_tier' => RaceTier::class,
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

    public function scopeOrderedForCatalog(Builder $query): Builder
    {
        return $query
            ->orderBy('race_type')
            ->orderByRaw("CASE race_tier WHEN 'amateur' THEN 1 WHEN 'semi_pro' THEN 2 WHEN 'pro' THEN 3 ELSE 4 END")
            ->orderBy('fuel_cost')
            ->orderBy('name');
    }

    public static function findByTypeAndTier(RaceType $raceType, RaceTier $raceTier): self
    {
        return static::query()
            ->where('race_type', $raceType)
            ->where('race_tier', $raceTier)
            ->firstOrFail();
    }

    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    public function opponentDriverStats(): array
    {
        return [
            'power' => (int) $this->opponent_stat_power,
            'acceleration' => (int) $this->opponent_stat_acceleration,
            'grip' => (int) $this->opponent_stat_grip,
            'handling' => (int) $this->opponent_stat_handling,
        ];
    }

    public function difficultyLabel(): string
    {
        return $this->resolvedTier()->difficultyLabel();
    }

    public function resolvedRaceType(): RaceType
    {
        return $this->race_type ?? RaceType::Circuit;
    }

    public function resolvedTier(): RaceTier
    {
        return $this->race_tier ?? RaceTier::Amateur;
    }
}
