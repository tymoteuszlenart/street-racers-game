<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use App\Services\ClubPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rankings_order_by_points(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        $low = Club::factory()->create(['name' => 'Low Points', 'slug' => 'low-points', 'points' => 10]);
        $high = Club::factory()->create(['name' => 'High Points', 'slug' => 'high-points', 'points' => 500]);
        $mid = Club::factory()->create(['name' => 'Mid Points', 'slug' => 'mid-points', 'points' => 100]);

        ClubMember::factory()->owner()->create(['club_id' => $high->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('clubs.rankings'));

        $response->assertOk();
        $response->assertSeeInOrder(['High Points', 'Mid Points', 'Low Points']);
        $response->assertSee('(your club)', false);
    }

    public function test_rankings_reflect_point_service_updates(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 10]);

        $club = Club::factory()->create(['name' => 'Rising Stars', 'slug' => 'rising-stars', 'points' => 0]);
        ClubMember::factory()->owner()->create(['club_id' => $club->id, 'user_id' => $user->id]);

        app(ClubPointService::class)->addPoints($club, 250);

        $response = $this->actingAs($user)->get(route('clubs.rankings'));

        $response->assertOk();
        $response->assertSee('250');
        $response->assertSee('Rising Stars');
    }

    public function test_rankings_forbidden_below_level_ten(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('clubs.rankings'))->assertForbidden();
    }
}
