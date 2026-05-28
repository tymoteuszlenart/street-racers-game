<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\FuelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FuelServiceTest extends TestCase
{
    use RefreshDatabase;

    private FuelService $fuelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fuelService = app(FuelService::class);
    }

    public function test_regenerates_fuel_from_timestamp(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 50,
            'fuel_max' => 100,
            'fuel_updated_at' => now()->subMinutes(25),
        ]);

        $this->fuelService->regenerate($profile->fresh());

        $profile->refresh();
        $this->assertSame(55, $profile->fuel_current);
    }

    public function test_regeneration_caps_at_fuel_max(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update([
            'fuel_current' => 95,
            'fuel_max' => 100,
            'fuel_updated_at' => now()->subHours(2),
        ]);

        $this->fuelService->regenerate($profile->fresh());

        $profile->refresh();
        $this->assertSame(100, $profile->fuel_current);
    }

    public function test_spend_fuel_reduces_balance(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 20]);

        $this->fuelService->spend($profile->fresh(), 10);

        $this->assertSame(10, $profile->fresh()->fuel_current);
    }

    public function test_spend_fuel_fails_when_insufficient(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 5]);

        $this->expectException(ValidationException::class);

        $this->fuelService->spend($profile->fresh(), 10);
    }
}
