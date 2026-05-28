<?php

namespace App\DTOs;

use App\Models\DailyReward;

class PremiumFuelClaimResult
{
    public function __construct(
        public readonly DailyReward $dailyReward,
        public readonly bool $replayed,
        public readonly int $premiumFuelGranted,
    ) {}
}
