<?php

namespace Tests\Feature;

use App\Enums\OpenCupMatchPhase;
use App\Enums\OpenCupStatus;
use App\Models\OpenCup;
use App\Models\OpenCupEntry;
use App\Models\OpenCupMatch;
use App\Models\User;
use App\Services\OpenCupAdvanceService;
use App\Services\OpenCupResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OpenCupTest extends TestCase
{
    use RefreshDatabase;

    private function cupReadyUser(int $cash = 20_000): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update([
            'level' => 5,
            'cash' => $cash,
        ]);

        return $user->fresh(['playerProfile']);
    }

    /**
     * @param  array{power?: int, acceleration?: int, grip?: int, handling?: int}  $statOverrides
     */
    private function boostEntryStats(OpenCupEntry $entry, array $statOverrides = []): void
    {
        $snapshot = $entry->car_snapshot;
        $snapshot['stats'] = array_merge($snapshot['stats'], $statOverrides);
        $entry->update(['car_snapshot' => $snapshot]);
    }

    public function test_hosting_creates_cup_and_charges_entry_fee(): void
    {
        $user = $this->cupReadyUser();
        $fee = (int) config('game.open_cup.entry_fee_cash', 2000);

        $response = $this->actingAs($user)->post(route('cups.store'));

        $response->assertRedirect();
        $this->assertSame(20_000 - $fee, $user->playerProfile->fresh()->cash);
        $this->assertDatabaseHas('open_cups', [
            'host_user_id' => $user->id,
            'status' => OpenCupStatus::Open->value,
        ]);
        $this->assertSame(1, OpenCupEntry::query()->where('user_id', $user->id)->count());
    }

    public function test_locked_below_unlock_level(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 4, 'cash' => 20_000]);

        $this->actingAs($user)->get(route('cups.index'))->assertForbidden();
    }

    public function test_cannot_join_second_active_cup(): void
    {
        $user = $this->cupReadyUser();
        $other = $this->cupReadyUser();

        $this->actingAs($user)->post(route('cups.store'))->assertRedirect();
        $cup = OpenCup::query()->firstOrFail();

        $this->actingAs($user)->post(route('cups.join', $cup))
            ->assertSessionHasErrors('cup');

        $this->actingAs($other)->post(route('cups.store'))->assertRedirect();
        $otherCup = OpenCup::query()->where('host_user_id', $other->id)->firstOrFail();

        $this->actingAs($user)->post(route('cups.join', $otherCup))
            ->assertSessionHasErrors('cup');
    }

    public function test_solo_cup_resolves_with_participation_rewards(): void
    {
        $user = $this->cupReadyUser();
        $this->actingAs($user)->post(route('cups.store'))->assertRedirect();

        $cup = OpenCup::query()->firstOrFail();
        $cup->update([
            'join_ends_at' => now()->subMinute(),
            'settling_ends_at' => now()->subMinute(),
            'status' => OpenCupStatus::Running,
        ]);

        app(OpenCupResolverService::class)
            ->withRandomUnit(fn (): float => 0.99)
            ->setupMatches($cup->fresh());

        app(OpenCupResolverService::class)
            ->withRandomUnit(fn (): float => 0.99)
            ->resolvePendingMatches($cup->fresh());

        app(OpenCupResolverService::class)->applyRewardsIfReady($cup->fresh());

        $cup->refresh();
        $this->assertSame(OpenCupStatus::Completed, $cup->status);
        $this->assertSame(3, OpenCupMatch::query()->where('phase', OpenCupMatchPhase::Solo)->count());

        $profile = $user->playerProfile->fresh();
        $this->assertGreaterThanOrEqual(1200, $profile->cash);
        $this->assertGreaterThanOrEqual(1, $profile->cups);
    }

    public function test_advance_command_moves_open_cup_through_settling_to_completed_solo(): void
    {
        $user = $this->cupReadyUser();
        $this->actingAs($user)->post(route('cups.store'));

        $cup = OpenCup::query()->firstOrFail();
        $cup->update(['join_ends_at' => now()->subMinute()]);

        Artisan::call('open-cup:advance');
        $cup->refresh();
        $this->assertSame(OpenCupStatus::Settling, $cup->status);

        $cup->update(['settling_ends_at' => now()->subMinute()]);
        Artisan::call('open-cup:advance');

        $cup->refresh();
        $this->assertSame(OpenCupStatus::Completed, $cup->status);
    }

    public function test_multiplayer_bracket_tie_eliminates_both(): void
    {
        $host = $this->cupReadyUser();
        $rival = $this->cupReadyUser();

        $this->actingAs($host)->post(route('cups.store'));
        $cup = OpenCup::query()->firstOrFail();
        $this->actingAs($rival)->post(route('cups.join', $cup));

        $entries = $cup->entries()->orderBy('id')->get();
        $this->boostEntryStats($entries[0], ['power' => 200, 'acceleration' => 200, 'grip' => 200, 'handling' => 200]);
        $this->boostEntryStats($entries[1], ['power' => 200, 'acceleration' => 200, 'grip' => 200, 'handling' => 200]);

        $cup->update([
            'status' => OpenCupStatus::Running,
            'join_ends_at' => now()->subHour(),
            'settling_ends_at' => now()->subHour(),
        ]);

        $resolver = app(OpenCupResolverService::class)->withRandomUnit(fn (): float => 0.5);
        $resolver->setupMatches($cup->fresh());
        $resolver->resolvePendingMatches($cup->fresh());
        $resolver->applyRewardsIfReady($cup->fresh());

        $bracketMatch = OpenCupMatch::query()
            ->where('open_cup_id', $cup->id)
            ->where('phase', OpenCupMatchPhase::Bracket)
            ->where('round', 1)
            ->firstOrFail();

        $this->assertTrue($bracketMatch->both_eliminated);
        $this->assertNull($cup->fresh()->champion_entry_id);
    }

    public function test_join_endpoint_adds_entrant(): void
    {
        $host = $this->cupReadyUser();
        $joiner = $this->cupReadyUser();

        $this->actingAs($host)->post(route('cups.store'));
        $cup = OpenCup::query()->firstOrFail();

        $this->actingAs($joiner)->post(route('cups.join', $cup))->assertRedirect();

        $this->assertSame(2, $cup->entries()->count());
    }

    public function test_advance_service_processes_running_cups(): void
    {
        $user = $this->cupReadyUser();
        $this->actingAs($user)->post(route('cups.store'));

        $cup = OpenCup::query()->firstOrFail();
        $cup->update([
            'status' => OpenCupStatus::Running,
            'join_ends_at' => now()->subHour(),
            'settling_ends_at' => now()->subHour(),
        ]);

        app(OpenCupAdvanceService::class)->advanceAll();

        $this->assertSame(OpenCupStatus::Completed, $cup->fresh()->status);
    }
}
