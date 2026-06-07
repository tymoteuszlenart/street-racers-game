<?php

namespace App\Models;

use App\Enums\OpenCupMatchPhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenCupMatch extends Model
{
    protected $fillable = [
        'open_cup_id',
        'phase',
        'round',
        'match_order',
        'entry_id',
        'entry_a_id',
        'entry_b_id',
        'race_result_id',
        'winner_entry_id',
        'both_eliminated',
    ];

    protected function casts(): array
    {
        return [
            'phase' => OpenCupMatchPhase::class,
            'both_eliminated' => 'boolean',
        ];
    }

    public function openCup(): BelongsTo
    {
        return $this->belongsTo(OpenCup::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(OpenCupEntry::class, 'entry_id');
    }

    public function entryA(): BelongsTo
    {
        return $this->belongsTo(OpenCupEntry::class, 'entry_a_id');
    }

    public function entryB(): BelongsTo
    {
        return $this->belongsTo(OpenCupEntry::class, 'entry_b_id');
    }

    public function raceResult(): BelongsTo
    {
        return $this->belongsTo(RaceResult::class);
    }

    public function winnerEntry(): BelongsTo
    {
        return $this->belongsTo(OpenCupEntry::class, 'winner_entry_id');
    }

    public function isResolved(): bool
    {
        return $this->race_result_id !== null || $this->both_eliminated;
    }
}
