<?php

namespace Database\Seeders;

use App\Services\ClubTournamentSeasonService;
use Illuminate\Database\Seeder;

class ClubTournamentSeeder extends Seeder
{
    public function run(): void
    {
        app(ClubTournamentSeasonService::class)->ensureCurrentSeasonExists();
    }
}
