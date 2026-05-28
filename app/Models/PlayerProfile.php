<?php

namespace App\Models;

use App\Observers\PlayerProfileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(PlayerProfileObserver::class)]
class PlayerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'cash',
        'reputation',
        'level',
        'experience',
        'fuel_current',
        'fuel_max',
        'fuel_updated_at',
        'premium_fuel_current',
        'premium_fuel_max',
        'premium_fuel_claimed_at',
    ];

    protected $casts = [
        'fuel_updated_at' => 'datetime',
        'premium_fuel_claimed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activeCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'active_car_id');
    }

    public function setActiveCarId(?int $carId): void
    {
        $this->active_car_id = $carId;
        $this->save();
    }
}
