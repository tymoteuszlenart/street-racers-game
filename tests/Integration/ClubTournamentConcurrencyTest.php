<?php

namespace Tests\Integration;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use App\Services\ClubTournamentSeasonService;
use Symfony\Component\Process\Process;

class ClubTournamentConcurrencyTest extends TestCase
{
    public function test_parallel_tournament_starts_with_one_premium_fuel_only_one_succeeds(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update([
            'level' => 15,
            'premium_fuel_current' => 1,
            'premium_fuel_max' => 5,
        ]);

        $club = Club::factory()->create(['slug' => 'fuel-race-crew']);
        ClubMember::factory()->owner()->create([
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);

        app(ClubTournamentSeasonService::class)->ensureCurrentSeasonExists();

        $outputs = $this->runParallelTournamentStarts($user->id, $club->id);

        $okCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'ok'));
        $errorCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'error'));

        $this->assertSame(1, $okCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(1, $errorCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(0, $user->playerProfile()->firstOrFail()->fresh()->premium_fuel_current);
    }

    /**
     * @param  list<int>  $userIds
     * @return list<array{status: string, message?: string}>
     */
    private function runParallelTournamentStarts(int $userId, int $clubId): array
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $script = base_path('tests/scripts/parallel_tournament_race.php');

        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $processes[] = new Process([
                $php,
                $script,
                (string) $userId,
                (string) $clubId,
                (string) $i,
            ], base_path(), null, null, 60);
        }

        foreach ($processes as $process) {
            $process->start();
        }

        $outputs = [];
        foreach ($processes as $process) {
            $process->wait();
            $outputs[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        }

        return $outputs;
    }
}
