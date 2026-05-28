<?php

namespace App\Services;

use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Transaction;

class TransactionService
{
    public function record(
        int $userId,
        TransactionType $type,
        TransactionCurrency $currency,
        int $amount,
        int $balanceAfter,
        string $sourceType,
        int $sourceId,
    ): Transaction {
        return Transaction::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'currency' => $currency,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'created_at' => now(),
        ]);
    }
}
