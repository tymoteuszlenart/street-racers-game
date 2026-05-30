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
        'stat_power',
        'stat_acceleration',
        'stat_grip',
        'stat_handling',
        'unspent_stat_points',
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

    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    public function driverStats(): array
    {
        return [
            'power' => (int) $this->stat_power,
            'acceleration' => (int) $this->stat_acceleration,
            'grip' => (int) $this->stat_grip,
            'handling' => (int) $this->stat_handling,
        ];
    }

    public function setActiveCarId(?int $carId): void
    {
        if ($carId !== null) {
            $car = Car::query()->findOrFail($carId);

            if ($car->user_id !== $this->user_id) {
                throw new \InvalidArgumentException('Active car must belong to the player.');
            }
        }

        $this->active_car_id = $carId;
        $this->save();
    }
}
