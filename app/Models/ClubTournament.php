<?php

namespace App\Models;

use App\Enums\ClubTournamentStatus;
use Database\Factories\ClubTournamentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubTournament extends Model
{
    /** @use HasFactory<ClubTournamentFactory> */
    use HasFactory;

    protected $fillable = [
        'season_key',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClubTournamentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ClubTournamentEntry::class);
    }

    public function rewardGrants(): HasMany
    {
        return $this->hasMany(ClubTournamentRewardGrant::class);
    }

    public function isActive(): bool
    {
        return $this->status === ClubTournamentStatus::Active;
    }
}
