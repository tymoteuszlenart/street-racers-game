<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PlayerLevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerLevelServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_experience_levels_up_when_threshold_is_reached(): void
    {
        config([
            'game.player.max_level' => 50,
            'game.player.experience_per_level' => 100,
        ]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 1, 'experience' => 0]);

        app(PlayerLevelService::class)->addExperience($profile, 100);
        $profile->save();

        $profile->refresh();
        $this->assertSame(2, $profile->level);
        $this->assertSame(100, $profile->experience);
    }

    public function test_level_does_not_exceed_configured_max(): void
    {
        config([
            'game.player.max_level' => 2,
            'game.player.experience_per_level' => 100,
        ]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 2, 'experience' => 500]);

        app(PlayerLevelService::class)->addExperience($profile, 1000);
        $profile->save();

        $profile->refresh();
        $this->assertSame(2, $profile->level);
    }

    public function test_progress_toward_next_level_reports_current_band(): void
    {
        config(['game.player.experience_per_level' => 100]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 2, 'experience' => 150]);

        $progress = app(PlayerLevelService::class)->progressTowardNextLevel($profile);

        $this->assertNotNull($progress);
        $this->assertSame(50, $progress['current']);
        $this->assertSame(100, $progress['required']);
        $this->assertSame(3, $progress['next_level']);
    }

    public function test_progress_toward_next_level_is_null_at_max_level(): void
    {
        config(['game.player.max_level' => 5]);

        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['level' => 5, 'experience' => 9999]);

        $this->assertNull(app(PlayerLevelService::class)->progressTowardNextLevel($profile));
    }
}
