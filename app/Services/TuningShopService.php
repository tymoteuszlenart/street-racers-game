<?php

namespace App\Services;

use App\Enums\PartAcquiredVia;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TuningShopService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function purchase(User $user, PartModel $partModel): Part
    {
        $profile = $user->playerProfile ?? throw ValidationException::withMessages([
            'part_model' => 'Player profile not found.',
        ]);

        $this->assertCanPurchase($profile, $partModel);

        return DB::transaction(function () use ($user, $partModel) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $partModel->refresh();

            $this->assertCanPurchase($profile, $partModel);

            $profile->cash -= $partModel->price;
            $profile->save();

            $part = Part::query()->create([
                'user_id' => $user->id,
                'part_model_id' => $partModel->id,
                'car_id' => null,
                'slot' => $partModel->slot,
                'acquired_via' => PartAcquiredVia::Shop,
                'purchase_price' => $partModel->price,
            ]);

            $this->transactionService->record(
                userId: $user->id,
                type: TransactionType::PartPurchase,
                currency: TransactionCurrency::Cash,
                amount: -$partModel->price,
                balanceAfter: $profile->cash,
                sourceType: $part->getMorphClass(),
                sourceId: $part->id,
            );

            return $part;
        });
    }

    private function assertCanPurchase(PlayerProfile $profile, PartModel $partModel): void
    {
        $partModel->assertPurchasableBy($profile);
    }
}
