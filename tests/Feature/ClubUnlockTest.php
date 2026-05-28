<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_forbidden_below_level_ten(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('clubs.index'))->assertForbidden();
    }

    public function test_join_forbidden_below_level_ten(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create(['slug' => 'locked-crew']);

        $this->actingAs($user)->post(route('clubs.join', $club))->assertForbidden();
    }
}
