<?php

namespace App\Services;

use App\DTOs\SellLineItem;
use App\DTOs\SellQuote;
use App\Enums\AcquiredVia;
use App\Enums\PartAcquiredVia;
use App\Models\Car;
use App\Models\Part;
use App\Models\PlayerProfile;
use App\Models\User;

class SellPriceCalculator
{
    public function quoteCar(User $user, Car $car, bool $includeEquippedParts = true): SellQuote
    {
        $car->loadMissing(['carModel', 'parts.partModel']);

        $blockedReason = $this->carBlockedReason($user, $car);
        if ($blockedReason !== null) {
            return new SellQuote([], 0, false, $blockedReason);
        }

        $lines = [$this->carLineItem($car)];

        if ($includeEquippedParts) {
            foreach ($car->parts as $part) {
                if ($part->acquired_via === PartAcquiredVia::Starter) {
                    $lines[] = $this->starterPartLineItem($part);

                    continue;
                }

                $partBlocked = $this->partBlockedReason($part, requireInventory: false);
                if ($partBlocked !== null) {
                    return new SellQuote([], 0, false, $partBlocked);
                }

                $lines[] = $this->partLineItem($part, bundled: true);
            }
        }

        return $this->buildQuote($lines);
    }

    public function quotePart(Part $part): SellQuote
    {
        $part->loadMissing('partModel');

        $blockedReason = $this->partBlockedReason($part, requireInventory: true);
        if ($blockedReason !== null) {
            return new SellQuote([], 0, false, $blockedReason);
        }

        return $this->buildQuote([$this->partLineItem($part, bundled: false)]);
    }

    private function carBlockedReason(User $user, Car $car): ?string
    {
        if ($car->acquired_via === AcquiredVia::Admin) {
            return 'This car cannot be sold.';
        }

        if (! in_array($car->acquired_via, [AcquiredVia::Dealer, AcquiredVia::Reward, AcquiredVia::Starter], true)) {
            return 'This car cannot be sold.';
        }

        $activeCarId = PlayerProfile::query()
            ->where('user_id', $user->id)
            ->value('active_car_id');

        if ($activeCarId === $car->id) {
            return 'Set another car as active before selling this one.';
        }

        if ($user->cars()->count() <= 1) {
            return 'You must keep at least one car.';
        }

        if ($car->acquired_via !== AcquiredVia::Starter) {
            $basis = $this->basisForCar($car);
            if ($basis <= 0) {
                return 'This car has no resale value.';
            }
        }

        return null;
    }

    private function partBlockedReason(Part $part, bool $requireInventory): ?string
    {
        if (in_array($part->acquired_via, [PartAcquiredVia::Admin, PartAcquiredVia::Starter], true)) {
            return 'This part cannot be sold.';
        }

        if (! in_array($part->acquired_via, [PartAcquiredVia::Shop, PartAcquiredVia::Reward], true)) {
            return 'This part cannot be sold.';
        }

        if ($requireInventory && $part->car_id !== null) {
            return 'Unequip this part or sell the car with upgrades installed.';
        }

        $basis = $this->basisForPart($part);
        if ($basis <= 0) {
            return 'This part has no resale value.';
        }

        return null;
    }

    private function carLineItem(Car $car): SellLineItem
    {
        $conditionPercent = $this->conditionPercent($car->condition_current, $car->condition_max);

        if ($car->acquired_via === AcquiredVia::Starter) {
            return new SellLineItem(
                kind: 'car',
                id: $car->id,
                label: $car->carModel->name,
                basis: 0,
                refundPercent: 0,
                conditionPercent: $conditionPercent,
                refund: 0,
            );
        }

        $basis = $this->basisForCar($car);
        $percent = $this->carRefundPercent($car);
        $refund = $this->computeRefund($basis, $percent, $conditionPercent);

        return new SellLineItem(
            kind: 'car',
            id: $car->id,
            label: $car->carModel->name,
            basis: $basis,
            refundPercent: $percent,
            conditionPercent: $conditionPercent,
            refund: $refund,
        );
    }

    private function starterPartLineItem(Part $part): SellLineItem
    {
        $conditionPercent = $this->conditionPercent($part->condition_current, $part->condition_max);

        return new SellLineItem(
            kind: 'part',
            id: $part->id,
            label: $part->partModel->name,
            basis: 0,
            refundPercent: 0,
            conditionPercent: $conditionPercent,
            refund: 0,
        );
    }

    private function partLineItem(Part $part, bool $bundled): SellLineItem
    {
        $basis = $this->basisForPart($part);
        $percent = $this->partRefundPercent($part, $bundled);
        $conditionPercent = $this->conditionPercent($part->condition_current, $part->condition_max);
        $refund = $this->computeRefund($basis, $percent, $conditionPercent);

        return new SellLineItem(
            kind: 'part',
            id: $part->id,
            label: $part->partModel->name,
            basis: $basis,
            refundPercent: $percent,
            conditionPercent: $conditionPercent,
            refund: $refund,
        );
    }

    /**
     * @param  list<SellLineItem>  $lines
     */
    private function buildQuote(array $lines): SellQuote
    {
        $total = array_sum(array_map(fn (SellLineItem $line) => $line->refund, $lines));

        return new SellQuote($lines, $total, true);
    }

    private function basisForCar(Car $car): int
    {
        if ($car->purchase_price !== null) {
            return (int) $car->purchase_price;
        }

        $car->loadMissing('carModel');

        return (int) $car->carModel->price;
    }

    private function basisForPart(Part $part): int
    {
        if ($part->purchase_price !== null) {
            return (int) $part->purchase_price;
        }

        $part->loadMissing('partModel');

        return (int) $part->partModel->price;
    }

    private function conditionPercent(int $current, int $max): float
    {
        if ($max <= 0) {
            return 100.0;
        }

        return ($current / $max) * 100;
    }

    private function computeRefund(int $basis, int $percent, float $conditionPercent): int
    {
        $ratio = $conditionPercent / 100;
        $refund = (int) floor($basis * ($percent / 100) * $ratio);
        $refund = min($refund, $basis);
        $minRefund = (int) config('game.sell.min_refund', 1);

        return max($minRefund, $refund);
    }

    private function carRefundPercent(Car $car): int
    {
        if ($car->acquired_via === AcquiredVia::Reward) {
            return (int) config('game.sell.reward_car_refund_percent', 80);
        }

        return (int) config('game.sell.car_refund_percent', 65);
    }

    private function partRefundPercent(Part $part, bool $bundled): int
    {
        if ($part->acquired_via === PartAcquiredVia::Reward) {
            return $bundled
                ? (int) config('game.sell.reward_bundled_part_refund_percent', 80)
                : (int) config('game.sell.reward_part_refund_percent', 80);
        }

        return $bundled
            ? (int) config('game.sell.bundled_part_refund_percent', 70)
            : (int) config('game.sell.part_refund_percent', 65);
    }
}
