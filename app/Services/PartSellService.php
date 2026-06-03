<?php

namespace App\Services;

use App\DTOs\SellQuote;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Part;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartSellService
{
    public function __construct(
        private readonly SellPriceCalculator $sellPriceCalculator,
        private readonly TransactionService $transactionService,
    ) {}

    public function sell(User $user, Part $part): SellQuote
    {
        if ($part->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'part' => ['You do not own this part.'],
            ]);
        }

        $quote = $this->sellPriceCalculator->quotePart($part);

        if (! $quote->sellable) {
            throw ValidationException::withMessages([
                'part' => [$quote->blockedReason ?? 'This part cannot be sold.'],
            ]);
        }

        return DB::transaction(function () use ($user, $part, $quote) {
            $part = Part::query()
                ->whereKey($part->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($part->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'part' => ['You do not own this part.'],
                ]);
            }

            $quote = $this->sellPriceCalculator->quotePart($part);

            if (! $quote->sellable) {
                throw ValidationException::withMessages([
                    'part' => [$quote->blockedReason ?? 'This part cannot be sold.'],
                ]);
            }

            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $partId = $part->id;

            $profile->cash += $quote->total;
            $profile->save();

            $this->transactionService->record(
                userId: $user->id,
                type: TransactionType::PartSale,
                currency: TransactionCurrency::Cash,
                amount: $quote->total,
                balanceAfter: $profile->cash,
                sourceType: $part->getMorphClass(),
                sourceId: $partId,
            );

            $part->delete();

            return $quote;
        });
    }
}
