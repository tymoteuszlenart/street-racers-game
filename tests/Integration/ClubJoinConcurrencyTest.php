<?php

namespace Tests\Integration;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Symfony\Component\Process\Process;

class ClubJoinConcurrencyTest extends TestCase
{
    public function test_parallel_join_when_club_has_one_slot_only_one_succeeds(): void
    {
        config(['game.clubs.max_members' => 2]);

        $owner = User::factory()->create();
        $owner->playerProfile()->update(['level' => 10]);

        $joinerA = User::factory()->create();
        $joinerA->playerProfile()->update(['level' => 10]);

        $joinerB = User::factory()->create();
        $joinerB->playerProfile()->update(['level' => 10]);

        $club = Club::factory()->create(['slug' => 'one-slot-crew']);
        ClubMember::factory()->owner()->create([
            'club_id' => $club->id,
            'user_id' => $owner->id,
        ]);

        $outputs = $this->runParallelClubJoins($club->id, [$joinerA->id, $joinerB->id]);

        $okCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'ok'));
        $errorCount = count(array_filter($outputs, fn (array $o) => $o['status'] === 'error'));

        $this->assertSame(1, $okCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(1, $errorCount, json_encode($outputs, JSON_THROW_ON_ERROR));
        $this->assertSame(2, ClubMember::query()->where('club_id', $club->id)->count());
    }

    /**
     * @param  list<int>  $userIds
     * @return list<array<string, mixed>>
     */
    private function runParallelClubJoins(int $clubId, array $userIds): array
    {
        $processes = [];

        foreach ($userIds as $userId) {
            $process = new Process([
                PHP_BINARY,
                'artisan',
                'clubs:integration-join',
                (string) $userId,
                (string) $clubId,
            ], base_path(), [
                'GAME_CLUBS_MAX_MEMBERS' => '2',
            ], null, 60);
            $process->start();
            $processes[] = $process;
        }

        $outputs = [];

        foreach ($processes as $process) {
            $process->wait();

            $rawOutput = trim($process->getOutput());

            if ($rawOutput === '') {
                $this->fail('Club join subprocess produced no output: '.$process->getErrorOutput());
            }

            $outputs[] = json_decode($rawOutput, true, 512, JSON_THROW_ON_ERROR);
        }

        return $outputs;
    }
}
