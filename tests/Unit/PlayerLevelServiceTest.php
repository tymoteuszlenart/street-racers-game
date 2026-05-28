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
}
