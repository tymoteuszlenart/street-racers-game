<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PlayerLevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerLevelServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlayerLevelService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlayerLevelService::class);
    }

    public function test_experience_cost_for_level_uses_quadratic_multiplier(): void
    {
        config(['game.player.experience.multiplier' => 50]);

        $this->assertSame(200, $this->service->experienceCostForLevel(2));
        $this->assertSame(500_000, $this->service->experienceCostForLevel(100));
    }

    public function test_cumulative_experience_for_level_100_matches_street_racer_total(): void
    {
        config(['game.player.experience.multiplier' => 50]);

        $this->assertSame(16_917_450, $this->service->cumulativeExperienceForLevel(100));
    }

    public function test_adding_experience_levels_up_when_threshold_is_reached(): void
    {
        config(['game.player.driver_stats.points_per_level' => 3]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 1, 'experience' => 0, 'unspent_stat_points' => 0]);

        $this->service->addExperience($profile, 200);
        $profile->save();

        $profile->refresh();
        $this->assertSame(2, $profile->level);
        $this->assertSame(200, $profile->experience);
        $this->assertSame(3, $profile->unspent_stat_points);
        $this->assertSame(1, $profile->stat_power);
    }

    public function test_level_does_not_exceed_configured_max(): void
    {
        config(['game.player.max_level' => 2]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 2, 'experience' => 500]);

        $this->service->addExperience($profile, 1000);
        $profile->save();

        $profile->refresh();
        $this->assertSame(2, $profile->level);
    }

    public function test_level_up_fills_fuel_tank(): void
    {
        config([
            'game.player.max_level' => 50,
            'game.player.experience.multiplier' => 50,
        ]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'level' => 1,
            'experience' => 0,
            'fuel_current' => 15,
            'fuel_max' => 100,
        ]);

        app(PlayerLevelService::class)->addExperience($profile, 200);
        $profile->save();
        $profile->refresh();

        $this->assertSame(2, $profile->level);
        $this->assertSame(100, $profile->fuel_current);
    }

    public function test_multiple_level_ups_grant_stat_points_for_each_level(): void
    {
        config(['game.player.driver_stats.points_per_level' => 3]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'level' => 1,
            'experience' => 0,
            'unspent_stat_points' => 0,
        ]);

        $this->service->addExperience($profile, 700);
        $profile->save();
        $profile->refresh();

        $this->assertSame(3, $profile->level);
        $this->assertSame(6, $profile->unspent_stat_points);
    }

    public function test_progress_toward_next_level_reports_current_band(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 2, 'experience' => 350]);

        $progress = $this->service->progressTowardNextLevel($profile);

        $this->assertNotNull($progress);
        $this->assertSame(150, $progress['current']);
        $this->assertSame(450, $progress['required']);
        $this->assertSame(3, $progress['next_level']);
    }

    public function test_progress_toward_next_level_is_null_at_max_level(): void
    {
        config(['game.player.max_level' => 5]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 5, 'experience' => 9999]);

        $this->assertNull($this->service->progressTowardNextLevel($profile));
    }

    public function test_add_experience_does_nothing_at_max_level(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $maxExperience = $this->service->maxExperience();
        $profile->update(['level' => 100, 'experience' => $maxExperience]);

        $this->service->addExperience($profile, 5000);
        $profile->save();
        $profile->refresh();

        $this->assertSame(100, $profile->level);
        $this->assertSame($maxExperience, $profile->experience);
    }

    public function test_experience_is_clamped_to_max_for_level_100(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 99, 'experience' => 0, 'unspent_stat_points' => 0]);

        $toLevel100 = $this->service->cumulativeExperienceForLevel(100);
        $this->service->addExperience($profile, $toLevel100 + 10_000);
        $profile->save();
        $profile->refresh();

        $this->assertSame(100, $profile->level);
        $this->assertSame($this->service->maxExperience(), $profile->experience);
    }
}
