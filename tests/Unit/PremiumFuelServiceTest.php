<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PremiumFuelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumFuelServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_capacity_allows_purchase_below_paid_cap_when_at_free_cap(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update([
            'premium_fuel_current' => 5,
            'premium_fuel_max' => 5,
        ]);

        $service = app(PremiumFuelService::class);

        $this->assertTrue($service->hasCapacity($profile));
    }

    public function test_has_capacity_false_at_paid_storage_cap(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile;
        $profile->update([
            'premium_fuel_current' => 20,
            'premium_fuel_max' => 20,
        ]);

        $service = app(PremiumFuelService::class);

        $this->assertFalse($service->hasCapacity($profile));
    }
}
