<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubJoinTest extends TestCase
{
    use RefreshDatabase;

    private function clubsReadyUser(array $profile = []): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(array_merge(['level' => 10], $profile));

        return $user;
    }

    public function test_player_can_join_open_club(): void
    {
        $owner = $this->clubsReadyUser();
        $joiner = $this->clubsReadyUser();
        $joiner->update(['name' => 'Joiner']);

        $club = Club::factory()->create(['name' => 'Open Crew', 'slug' => 'open-crew']);
        ClubMember::factory()->owner()->create([
            'club_id' => $club->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($joiner)->post(route('clubs.join', $club));

        $response->assertRedirect(route('clubs.show', $club));

        $this->assertDatabaseHas('club_members', [
            'club_id' => $club->id,
            'user_id' => $joiner->id,
            'role' => 'member',
        ]);
    }

    public function test_full_club_rejected(): void
    {
        config(['game.clubs.max_members' => 2]);

        $owner = $this->clubsReadyUser();
        $member = $this->clubsReadyUser();
        $joiner = $this->clubsReadyUser();

        $club = Club::factory()->create(['slug' => 'full-crew']);
        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $owner->id]);
        ClubMember::factory()->create(['club_id' => $club->id, 'user_id' => $member->id]);

        $response = $this->actingAs($joiner)->post(route('clubs.join', $club));

        $response->assertForbidden();
        $this->assertSame(2, ClubMember::query()->where('club_id', $club->id)->count());
    }

    public function test_cannot_join_second_club(): void
    {
        $user = $this->clubsReadyUser();
        $clubA = Club::factory()->create(['slug' => 'club-a']);
        $clubB = Club::factory()->create(['slug' => 'club-b']);

        ClubMember::factory()->create(['club_id' => $clubA->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('clubs.join', $clubB));

        $response->assertForbidden();
        $this->assertSame(1, ClubMember::query()->where('user_id', $user->id)->count());
    }
}
