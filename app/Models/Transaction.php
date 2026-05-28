<?php

namespace App\Models;

use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'currency',
        'amount',
        'balance_after',
        'source_type',
        'source_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'currency' => TransactionCurrency::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
