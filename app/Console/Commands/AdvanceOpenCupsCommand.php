<?php

namespace App\Console\Commands;

use App\Services\OpenCupAdvanceService;
use Illuminate\Console\Command;

class AdvanceOpenCupsCommand extends Command
{
    protected $signature = 'open-cup:advance';

    protected $description = 'Advance Open Cup join windows, settling, and race resolution';

    public function handle(OpenCupAdvanceService $advanceService): int
    {
        $count = $advanceService->advanceAll();

        $this->info("Processed {$count} running Open Cup(s).");

        return self::SUCCESS;
    }
}
