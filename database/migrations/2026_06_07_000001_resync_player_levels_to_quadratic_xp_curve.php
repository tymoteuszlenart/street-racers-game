<?php

use App\Models\PlayerProfile;
use App\Services\PlayerLevelService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $playerLevelService = app(PlayerLevelService::class);

        PlayerProfile::query()->eachById(function (PlayerProfile $profile) use ($playerLevelService): void {
            $playerLevelService->syncLevel($profile);
            $profile->save();
        });
    }

    public function down(): void
    {
        // Irreversible: levels were corrected to match total experience.
    }
};
