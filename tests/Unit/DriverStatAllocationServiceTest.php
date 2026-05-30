<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DriverStatAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DriverStatAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocate_applies_increments_and_reduces_unspent_points(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'unspent_stat_points' => 5,
            'stat_power' => 2,
            'stat_acceleration' => 2,
            'stat_grip' => 2,
            'stat_handling' => 2,
        ]);

        app(DriverStatAllocationService::class)->allocate($profile, [
            'power' => 3,
            'acceleration' => 1,
            'grip' => 1,
            'handling' => 0,
        ]);

        $profile->refresh();
        $this->assertSame(5, $profile->stat_power);
        $this->assertSame(3, $profile->stat_acceleration);
        $this->assertSame(0, $profile->unspent_stat_points);
    }

    public function test_allocate_rejects_more_points_than_available(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['unspent_stat_points' => 2]);

        $this->expectException(ValidationException::class);

        app(DriverStatAllocationService::class)->allocate($profile, [
            'power' => 3,
            'acceleration' => 0,
            'grip' => 0,
            'handling' => 0,
        ]);
    }
}
