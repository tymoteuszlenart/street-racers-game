<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvpRace extends Model
{
    protected $fillable = [
        'challenger_user_id',
        'defender_user_id',
        'challenger_car_id',
        'defender_car_id',
        'challenger_snapshot',
        'defender_snapshot',
        'race_result_id',
    ];

    protected function casts(): array
    {
        return [
            'challenger_snapshot' => 'array',
            'defender_snapshot' => 'array',
        ];
    }

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_user_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defender_user_id');
    }

    public function challengerCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'challenger_car_id');
    }

    public function defenderCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'defender_car_id');
    }

    public function raceResult(): BelongsTo
    {
        return $this->belongsTo(RaceResult::class);
    }
}
