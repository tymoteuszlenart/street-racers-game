<?php

namespace App\Models;

use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'points',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'level' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClubMember::class);
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ClubMember::class, 'club_id', 'id', 'id', 'user_id');
    }

    public function tournamentEntries(): HasMany
    {
        return $this->hasMany(ClubTournamentEntry::class);
    }

    public function isFull(): bool
    {
        $count = $this->members_count ?? $this->members()->count();

        return $count >= config('game.clubs.max_members');
    }
}
