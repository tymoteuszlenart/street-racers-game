<?php

namespace App\DTOs;

final readonly class SellQuote
{
    /**
     * @param  list<SellLineItem>  $lines
     */
    public function __construct(
        public array $lines,
        public int $total,
        public bool $sellable,
        public ?string $blockedReason = null,
    ) {}
}
