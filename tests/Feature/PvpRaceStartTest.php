<?php

namespace Tests\Feature;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\PvpRace;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\CarStatAggregator;
use App\Services\PvpRaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PvpRaceStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_player_can_start_pvp_race(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        $response = $this->actingAs($challenger)->post(route('pvp.start', $defender), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect();
        $this->assertSame(1, PvpRace::query()->count());
        $this->assertSame(1, RaceResult::query()->where('attempt_type', 'pvp')->count());
        $this->assertSame(90, $challenger->playerProfile()->firstOrFail()->fresh()->fuel_current);
    }

    public function test_self_race_is_blocked(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->from(route('pvp.index'))->post(route('pvp.start', $user), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('pvp.index'));
        $response->assertSessionHasErrors('pvp');
        $this->assertSame(0, PvpRace::query()->count());
    }

    public function test_json_self_race_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('pvp.start', $user), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('pvp');
        $this->assertSame(0, PvpRace::query()->count());
    }

    public function test_opponent_without_active_car_is_blocked(): void
    {
        $challenger = User::factory()->create();
        $challenger->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $defender = User::factory()->create();
        $defender->playerProfile()->firstOrFail()->forceFill(['active_car_id' => null])->save();

        $response = $this->actingAs($challenger)->from(route('pvp.index'))->post(route('pvp.start', $defender), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('pvp.index'));
        $response->assertSessionHasErrors('defender');
        $this->assertSame(0, PvpRace::query()->count());
    }

    public function test_defender_spends_no_fuel_and_takes_no_condition_damage(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        $defenderProfile = $defender->playerProfile()->firstOrFail();
        $defenderCar = $defenderProfile->activeCar()->firstOrFail();
        $defenderFuelBefore = $defenderProfile->fuel_current;
        $defenderConditionBefore = $defenderCar->condition_current;

        $service = app(PvpRaceService::class)->withRandomUnit(fn (): float => 0.5);

        $service->startPvpRace($challenger, $defender, (string) Str::uuid());

        $defenderProfile->refresh();
        $defenderCar->refresh();

        $this->assertSame($defenderFuelBefore, $defenderProfile->fuel_current);
        $this->assertSame($defenderConditionBefore, $defenderCar->condition_current);
    }

    public function test_pvp_win_grants_cash_and_reputation_scaled_to_defender_level(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        $defender->playerProfile()->firstOrFail()->activeCar()->firstOrFail()->update([
            'condition_current' => 1,
        ]);

        $profile = $challenger->playerProfile()->firstOrFail();
        $cashBefore = $profile->cash;
        $reputationBefore = $profile->reputation;
        $experienceBefore = $profile->experience;

        $result = app(PvpRaceService::class)
            ->withRandomUnit(fn (): float => 0.9)
            ->startPvpRace($challenger, $defender, (string) Str::uuid());

        $profile->refresh();

        $this->assertTrue($result->raceResult->won);
        $this->assertSame(140, $profile->cash - $cashBefore);
        $this->assertSame(6, $profile->reputation - $reputationBefore);
        $this->assertSame($experienceBefore, $profile->experience);
        $this->assertSame(
            ['cash' => 140, 'reputation' => 6, 'opponent_level' => 1],
            $result->raceResult->score_breakdown['rewards'],
        );
    }

    public function test_pvp_loss_pays_more_cash_against_higher_level_defender(): void
    {
        [$challenger, $lowDefender] = $this->twoPlayersWithFuel();
        [, $highDefender] = $this->twoPlayersWithFuel();

        $highDefender->playerProfile()->firstOrFail()->update(['level' => 12]);

        $service = app(PvpRaceService::class)->withRandomUnit(fn (): float => 0.0);

        $lowResult = $service->startPvpRace($challenger, $lowDefender, (string) Str::uuid());
        $this->assertFalse($lowResult->raceResult->won);

        $challenger->playerProfile()->firstOrFail()->update(['fuel_current' => 100]);

        $highResult = $service->startPvpRace($challenger, $highDefender, (string) Str::uuid());
        $this->assertFalse($highResult->raceResult->won);

        $this->assertGreaterThan(
            $lowResult->raceResult->score_breakdown['rewards']['cash'],
            $highResult->raceResult->score_breakdown['rewards']['cash'],
        );
    }

    public function test_pvp_loss_grants_consolation_rewards_without_experience(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        $defender->playerProfile()->firstOrFail()->update(['level' => 3]);

        $profile = $challenger->playerProfile()->firstOrFail();
        $cashBefore = $profile->cash;
        $reputationBefore = $profile->reputation;

        $result = app(PvpRaceService::class)
            ->withRandomUnit(fn (): float => 0.0)
            ->startPvpRace($challenger, $defender, (string) Str::uuid());

        $profile->refresh();

        $this->assertFalse($result->raceResult->won);
        $this->assertSame(49, $profile->cash - $cashBefore);
        $this->assertSame(2, $profile->reputation - $reputationBefore);
    }

    public function test_snapshots_remain_stable_after_later_garage_changes(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        $defenderCar = $defender->playerProfile()->firstOrFail()->activeCar()->firstOrFail();
        $defenderCar->parts()->where('slot', PartSlot::Engine)->forceDelete();

        $service = app(PvpRaceService::class)->withRandomUnit(fn (): float => 0.0);

        $first = $service->startPvpRace($challenger, $defender, (string) Str::uuid());

        $partModel = PartModel::factory()->create([
            'slot' => PartSlot::Engine,
            'power_bonus' => 50,
            'acceleration_bonus' => 50,
            'grip_bonus' => 50,
            'handling_bonus' => 50,
        ]);

        Part::query()->create([
            'user_id' => $defender->id,
            'part_model_id' => $partModel->id,
            'car_id' => $defenderCar->id,
            'slot' => PartSlot::Engine,
            'acquired_via' => PartAcquiredVia::Shop,
        ]);

        $defenderCar->update(['condition_current' => 1]);

        $pvpRace = $first->pvpRace->fresh();
        $storedSnapshot = $pvpRace->defender_snapshot;

        $this->assertSame(
            $first->raceResult->opponent_score,
            RaceResult::query()->findOrFail($first->raceResult->id)->opponent_score,
        );
        $this->assertSame($storedSnapshot, $pvpRace->fresh()->defender_snapshot);

        $liveStats = app(CarStatAggregator::class)->aggregate($defenderCar->fresh());
        $this->assertNotSame($storedSnapshot['stats']['power'], $liveStats['power']);
        $this->assertNotSame($storedSnapshot['stats']['condition_percent'], $liveStats['condition_percent']);
    }

    public function test_same_pair_daily_cap_blocks_eleventh_race_in_either_direction(): void
    {
        config(['game.pvp.daily_pair_cap' => 10]);

        [$playerA, $playerB] = $this->twoPlayersWithFuel();
        $service = app(PvpRaceService::class)->withRandomUnit(fn (): float => 0.5);

        for ($i = 0; $i < 5; $i++) {
            $service->startPvpRace($playerA, $playerB, (string) Str::uuid());
            $playerA->playerProfile()->firstOrFail()->update(['fuel_current' => 100]);
            $playerB->playerProfile()->firstOrFail()->update(['fuel_current' => 100]);
            $service->startPvpRace($playerB, $playerA, (string) Str::uuid());
            $playerA->playerProfile()->firstOrFail()->update(['fuel_current' => 100]);
            $playerB->playerProfile()->firstOrFail()->update(['fuel_current' => 100]);
        }

        $this->assertSame(10, PvpRace::query()->count());

        $response = $this->actingAs($playerA)->from(route('pvp.index'))->post(route('pvp.start', $playerB), [
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('pvp.index'));
        $response->assertSessionHasErrors('pvp');
        $this->assertSame(10, PvpRace::query()->count());
    }

    public function test_challenger_pvp_result_page_requires_ownership(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        $result = app(PvpRaceService::class)
            ->withRandomUnit(fn (): float => 0.5)
            ->startPvpRace($challenger, $defender, (string) Str::uuid());

        $this->actingAs($defender)
            ->get(route('pvp.show', $result->raceResult))
            ->assertForbidden();
    }

    public function test_defender_can_view_pvp_history(): void
    {
        [$challenger, $defender] = $this->twoPlayersWithFuel();

        app(PvpRaceService::class)
            ->withRandomUnit(fn (): float => 0.5)
            ->startPvpRace($challenger, $defender, (string) Str::uuid());

        $this->actingAs($defender)
            ->get(route('pvp.history'))
            ->assertOk()
            ->assertSee($challenger->name);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function twoPlayersWithFuel(): array
    {
        $challenger = User::factory()->create();
        $defender = User::factory()->create();

        $challenger->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);
        $defender->playerProfile()->firstOrFail()->update([
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        return [$challenger, $defender];
    }
}
