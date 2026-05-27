<?php

namespace App\Observers;

use App\Models\PlayerProfile;
use App\Services\StarterCarService;

class PlayerProfileObserver
{
    public function __construct(
        private readonly StarterCarService $starterCarService,
    ) {}

    public function created(PlayerProfile $playerProfile): void
    {
        $this->starterCarService->assignToProfile($playerProfile);
    }
}
