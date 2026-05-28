<?php

namespace Tests\Feature;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubCreateTest extends TestCase
{
    use RefreshDatabase;

    private function clubsReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        return $user;
    }

    public function test_guest_cannot_create_club(): void
    {
        $this->get(route('clubs.create'))->assertRedirect(route('login'));
    }

    public function test_create_forbidden_below_level_ten(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('clubs.create'))->assertForbidden();
        $this->actingAs($user)->post(route('clubs.store'), [
            'name' => 'Test Club',
        ])->assertForbidden();
    }

    public function test_player_can_create_club_at_level_ten(): void
    {
        $user = $this->clubsReadyUser();

        $response = $this->actingAs($user)->post(route('clubs.store'), [
            'name' => 'Neon Drift',
            'description' => 'We race at night.',
        ]);

        $club = Club::query()->where('name', 'Neon Drift')->first();
        $this->assertNotNull($club);

        $response->assertRedirect(route('clubs.show', $club));

        $this->assertDatabaseHas('club_members', [
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => ClubRole::Owner->value,
        ]);
    }

    public function test_duplicate_membership_blocked_when_creating(): void
    {
        $user = $this->clubsReadyUser();
        ClubMember::factory()->owner()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('clubs.store'), [
            'name' => 'Second Club',
        ]);

        $response->assertForbidden();
    }

    public function test_unique_name_enforced_case_insensitive(): void
    {
        $user = $this->clubsReadyUser();
        Club::factory()->create(['name' => 'Street Kings', 'slug' => 'street-kings']);

        $response = $this->actingAs($user)->post(route('clubs.store'), [
            'name' => 'street kings',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Club::query()->count());
    }
}
