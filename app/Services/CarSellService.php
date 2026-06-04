<?php

namespace App\Services;

use App\DTOs\SellQuote;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Car;
use App\Models\Part;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarSellService
{
    public function __construct(
        private readonly SellPriceCalculator $sellPriceCalculator,
        private readonly TransactionService $transactionService,
    ) {}

    public function sell(User $user, Car $car): SellQuote
    {
        if ($car->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'car' => ['You do not own this car.'],
            ]);
        }

        $quote = $this->sellPriceCalculator->quoteCar($user, $car, includeEquippedParts: true);

        if (! $quote->sellable) {
            throw ValidationException::withMessages([
                'car' => [$quote->blockedReason ?? 'This car cannot be sold.'],
            ]);
        }

        return DB::transaction(function () use ($user, $car, $quote) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $car = Car::query()
                ->whereKey($car->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($car->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'car' => ['You do not own this car.'],
                ]);
            }

            $equippedParts = Part::query()
                ->where('car_id', $car->id)
                ->lockForUpdate()
                ->get();

            $quote = $this->sellPriceCalculator->quoteCar($user, $car->load('parts.partModel'), includeEquippedParts: true);

            if (! $quote->sellable) {
                throw ValidationException::withMessages([
                    'car' => [$quote->blockedReason ?? 'This car cannot be sold.'],
                ]);
            }

            $carId = $car->id;
            $profile->cash += $quote->total;
            $profile->save();

            $this->transactionService->record(
                userId: $user->id,
                type: TransactionType::CarSale,
                currency: TransactionCurrency::Cash,
                amount: $quote->total,
                balanceAfter: $profile->cash,
                sourceType: $car->getMorphClass(),
                sourceId: $carId,
            );

            foreach ($equippedParts as $part) {
                $part->delete();
            }

            $car->delete();

            return $quote;
        });
    }
}
