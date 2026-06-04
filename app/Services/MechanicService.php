<?php

namespace App\Services;

use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Car;
use App\Models\Part;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MechanicService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function upgradeCost(Part $part): int
    {
        $part->loadMissing('partModel');
        $nextLevel = $part->upgrade_level + 1;
        $percent = (int) config('game.mechanic.upgrade_cost_percent_of_price_per_level', 15);
        $basis = $part->purchase_price ?? $part->partModel->price;

        return max(1, (int) floor($basis * $nextLevel * $percent / 100));
    }

    public function repairCarCost(Car $car): int
    {
        $missing = $car->condition_max - $car->condition_current;

        if ($missing <= 0) {
            return 0;
        }

        $base = (int) config('game.mechanic.repair.car_base_cost', 50);
        $perPoint = (int) config('game.mechanic.repair.cost_per_missing_condition_point', 2);

        return $base + ($missing * $perPoint);
    }

    public function repairPartCost(Part $part): int
    {
        $missing = $part->condition_max - $part->condition_current;

        if ($missing <= 0) {
            return 0;
        }

        $base = (int) config('game.mechanic.repair.part_base_cost', 25);
        $perPoint = (int) config('game.mechanic.repair.cost_per_missing_condition_point', 2);

        return $base + ($missing * $perPoint);
    }

    public function upgradePart(User $user, Part $part): Part
    {
        if ($part->user_id !== $user->id) {
            throw ValidationException::withMessages(['part' => 'You do not own this part.']);
        }

        $maxLevel = (int) config('game.mechanic.max_upgrade_level', 9);

        if ($part->upgrade_level >= $maxLevel) {
            throw ValidationException::withMessages(['part' => 'This part is already at maximum tune level.']);
        }

        $cost = $this->upgradeCost($part);

        return DB::transaction(function () use ($user, $part, $cost) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $part = Part::query()->whereKey($part->id)->lockForUpdate()->firstOrFail();

            if ($profile->cash < $cost) {
                throw ValidationException::withMessages(['cash' => 'Not enough cash for this upgrade.']);
            }

            $profile->cash -= $cost;
            $profile->save();

            $part->upgrade_level++;
            $part->save();

            $this->transactionService->record(
                userId: $user->id,
                type: TransactionType::PartUpgrade,
                currency: TransactionCurrency::Cash,
                amount: -$cost,
                balanceAfter: $profile->cash,
                sourceType: $part->getMorphClass(),
                sourceId: $part->id,
            );

            return $part->fresh(['partModel', 'car.carModel']);
        });
    }

    public function repairCar(User $user, Car $car): Car
    {
        if ($car->user_id !== $user->id) {
            throw ValidationException::withMessages(['car' => 'You do not own this car.']);
        }

        $cost = $this->repairCarCost($car);

        if ($cost <= 0) {
            throw ValidationException::withMessages(['car' => 'This car does not need repairs.']);
        }

        return DB::transaction(function () use ($user, $car, $cost) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $car = Car::query()->whereKey($car->id)->lockForUpdate()->firstOrFail();

            $cost = $this->repairCarCost($car);

            if ($cost <= 0) {
                throw ValidationException::withMessages(['car' => 'This car does not need repairs.']);
            }

            if ($profile->cash < $cost) {
                throw ValidationException::withMessages(['cash' => 'Not enough cash for repairs.']);
            }

            $profile->cash -= $cost;
            $profile->save();

            $car->condition_current = $car->condition_max;
            $car->save();

            $this->transactionService->record(
                userId: $user->id,
                type: TransactionType::CarRepair,
                currency: TransactionCurrency::Cash,
                amount: -$cost,
                balanceAfter: $profile->cash,
                sourceType: $car->getMorphClass(),
                sourceId: $car->id,
            );

            return $car->fresh(['carModel']);
        });
    }

    public function repairPart(User $user, Part $part): Part
    {
        if ($part->user_id !== $user->id) {
            throw ValidationException::withMessages(['part' => 'You do not own this part.']);
        }

        $cost = $this->repairPartCost($part);

        if ($cost <= 0) {
            throw ValidationException::withMessages(['part' => 'This part does not need repairs.']);
        }

        return DB::transaction(function () use ($user, $part, $cost) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $part = Part::query()->whereKey($part->id)->lockForUpdate()->firstOrFail();

            $cost = $this->repairPartCost($part);

            if ($cost <= 0) {
                throw ValidationException::withMessages(['part' => 'This part does not need repairs.']);
            }

            if ($profile->cash < $cost) {
                throw ValidationException::withMessages(['cash' => 'Not enough cash for repairs.']);
            }

            $profile->cash -= $cost;
            $profile->save();

            $part->condition_current = $part->condition_max;
            $part->save();

            $this->transactionService->record(
                userId: $user->id,
                type: TransactionType::PartRepair,
                currency: TransactionCurrency::Cash,
                amount: -$cost,
                balanceAfter: $profile->cash,
                sourceType: $part->getMorphClass(),
                sourceId: $part->id,
            );

            return $part->fresh(['partModel', 'car.carModel']);
        });
    }
}
