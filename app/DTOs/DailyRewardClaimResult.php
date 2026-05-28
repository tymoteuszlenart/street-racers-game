<?php

namespace App\DTOs;

use App\Models\DailyReward;

class DailyRewardClaimResult
{
    public function __construct(
        public readonly DailyReward $dailyReward,
        public readonly bool $replayed,
        public readonly int $fuelGranted,
    ) {}
}
