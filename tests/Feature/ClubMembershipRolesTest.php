<?php

namespace Tests\Feature;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubMembershipRolesTest extends TestCase
{
    use RefreshDatabase;

    private function clubsReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        return $user;
    }

    public function test_manager_can_kick_member(): void
    {
        $manager = $this->clubsReadyUser();
        $member = $this->clubsReadyUser();
        $club = Club::factory()->create(['slug' => 'kick-test']);

        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $this->clubsReadyUser()->id]);
        ClubMember::factory()->manager()->create(['club_id' => $club->id, 'user_id' => $manager->id]);
        $target = ClubMember::factory()->create(['club_id' => $club->id, 'user_id' => $member->id]);

        $response = $this->actingAs($manager)->delete(route('clubs.members.kick', [$club, $target]));

        $response->assertRedirect(route('clubs.show', $club));
        $this->assertDatabaseMissing('club_members', ['id' => $target->id]);
    }

    public function test_member_cannot_kick(): void
    {
        $actor = $this->clubsReadyUser();
        $targetUser = $this->clubsReadyUser();
        $club = Club::factory()->create(['slug' => 'no-kick']);

        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $this->clubsReadyUser()->id]);
        ClubMember::factory()->create(['club_id' => $club->id, 'user_id' => $actor->id]);
        $target = ClubMember::factory()->create(['club_id' => $club->id, 'user_id' => $targetUser->id]);

        $response = $this->actingAs($actor)->delete(route('clubs.members.kick', [$club, $target]));

        $response->assertForbidden();
        $this->assertDatabaseHas('club_members', ['id' => $target->id]);
    }

    public function test_owner_can_transfer_ownership_and_leave(): void
    {
        $owner = $this->clubsReadyUser();
        $successor = $this->clubsReadyUser();
        $club = Club::factory()->create(['slug' => 'transfer-test']);

        $ownerMembership = ClubMember::factory()->owner()->create([
            'club_id' => $club->id,
            'user_id' => $owner->id,
        ]);
        $successorMembership = ClubMember::factory()->create([
            'club_id' => $club->id,
            'user_id' => $successor->id,
        ]);

        $this->actingAs($owner)->post(route('clubs.transfer-ownership', $club), [
            'member_id' => $successorMembership->id,
        ])->assertRedirect(route('clubs.show', $club));

        $this->assertSame(ClubRole::Owner, $successorMembership->fresh()->role);
        $this->assertSame(ClubRole::Manager, $ownerMembership->fresh()->role);

        $this->actingAs($owner)->post(route('clubs.leave', $club))
            ->assertRedirect(route('clubs.index'));

        $this->assertDatabaseMissing('club_members', ['id' => $ownerMembership->id]);
    }

    public function test_owner_cannot_leave_without_transfer(): void
    {
        $owner = $this->clubsReadyUser();
        $club = Club::factory()->create(['slug' => 'stuck-owner']);

        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $owner->id]);
        ClubMember::factory()->create(['club_id' => $club->id, 'user_id' => $this->clubsReadyUser()->id]);

        $response = $this->actingAs($owner)->post(route('clubs.leave', $club));

        $response->assertForbidden();
    }
}
