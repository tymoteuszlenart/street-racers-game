<?php

namespace App\DTOs;

final readonly class SellLineItem
{
    public function __construct(
        public string $kind,
        public int $id,
        public string $label,
        public int $basis,
        public int $refundPercent,
        public float $conditionPercent,
        public int $refund,
    ) {}
}
