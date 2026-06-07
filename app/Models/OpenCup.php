<?php

namespace App\Models;

use App\Enums\OpenCupStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenCup extends Model
{
    protected $fillable = [
        'host_user_id',
        'status',
        'entry_fee_cash',
        'host_snapshot',
        'join_ends_at',
        'settling_ends_at',
        'champion_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OpenCupStatus::class,
            'host_snapshot' => 'array',
            'join_ends_at' => 'datetime',
            'settling_ends_at' => 'datetime',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(OpenCupEntry::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(OpenCupMatch::class);
    }

    public function championEntry(): BelongsTo
    {
        return $this->belongsTo(OpenCupEntry::class, 'champion_entry_id');
    }

    public function isJoinable(): bool
    {
        return $this->status === OpenCupStatus::Open
            && $this->join_ends_at->isFuture()
            && $this->entries()->count() < (int) config('game.open_cup.max_entrants', 8);
    }
}
