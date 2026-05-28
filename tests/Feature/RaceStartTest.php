<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RaceStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_player_can_start_race_with_idempotency_key(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $response = $this->actingAs($user)->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect();
        $this->assertSame(90, $profile->fresh()->fuel_current);
    }

    public function test_insufficient_fuel_is_rejected(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 0, 'fuel_updated_at' => now()]);

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('fuel');
        $this->assertSame(0, $profile->fresh()->fuel_current);
    }

    public function test_missing_active_car_is_rejected(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->forceFill([
            'active_car_id' => null,
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ])->save();

        $race = Race::query()->where('name', 'Downtown Sprint')->firstOrFail();

        $response = $this->actingAs($user)->from(route('races.index'))->post(route('races.start', $race), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('races.index'));
        $response->assertSessionHasErrors('active_car');
    }
}
