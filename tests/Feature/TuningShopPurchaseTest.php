<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TuningShopPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function tuningReadyUser(): User
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['level' => 5, 'cash' => 10000]);

        return $user;
    }

    public function test_shop_parts_purchase_forbidden_below_level_five(): void
    {
        $user = User::factory()->create();
        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();

        $this->actingAs($user)->post(route('shop.parts.purchase', $partModel))->assertForbidden();
    }

    public function test_shop_index_shows_engine_parts_tab_at_level_five(): void
    {
        $user = $this->tuningReadyUser();

        $this->actingAs($user)->get(route('shop.index', ['tab' => 'engine']))->assertOk();
    }

    public function test_shop_index_legacy_parts_tab_redirects_to_first_slot(): void
    {
        $user = $this->tuningReadyUser();

        $this->actingAs($user)->get(route('shop.index', ['tab' => 'parts']))->assertOk();
    }

    public function test_part_purchase_deducts_cash_and_creates_inventory_row(): void
    {
        $user = $this->tuningReadyUser();
        $profile = $user->playerProfile()->firstOrFail();

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $expectedCash = $profile->cash - $partModel->price;

        $response = $this->actingAs($user)->post(route('tuning.purchase', $partModel));

        $response->assertRedirect(route('shop.index', ['tab' => $partModel->slot->value]));
        $this->assertSame($expectedCash, $profile->fresh()->cash);

        $part = Part::query()
            ->where('user_id', $user->id)
            ->where('part_model_id', $partModel->id)
            ->first();

        $this->assertNotNull($part);
        $this->assertNull($part->car_id);
        $this->assertSame('shop', $part->acquired_via->value);
        $this->assertSame($partModel->price, $part->purchase_price);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::PartPurchase->value,
            'amount' => -$partModel->price,
        ]);
    }

    public function test_part_purchase_rejects_insufficient_cash(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['cash' => 100]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();

        $response = $this->actingAs($user)->post(route('tuning.purchase', $partModel));

        $response->assertSessionHasErrors('cash');
        $this->assertSame(0, Part::query()->where('user_id', $user->id)->count());
    }

    public function test_part_purchase_rejects_below_unlock_level(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['level' => 5, 'cash' => 50000]);

        $partModel = PartModel::query()->where('name', 'Competition Mill')->firstOrFail();

        $response = $this->actingAs($user)->post(route('tuning.purchase', $partModel));

        $response->assertSessionHasErrors('part_model');
        $this->assertSame(0, Part::query()->where('user_id', $user->id)->count());
    }

    public function test_part_purchase_forbidden_below_level_five(): void
    {
        $user = User::factory()->create();
        $user->playerProfile()->update(['cash' => 50000]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();

        $this->actingAs($user)->post(route('tuning.purchase', $partModel))->assertForbidden();
    }

    public function test_tuning_shop_index_hides_parts_below_player_block_level(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['level' => 11]);

        $outgrown = PartModel::query()->where('name', 'Street Block')->firstOrFail();

        $response = $this->actingAs($user)->get(route('shop.index', ['tab' => 'engine']));

        $response->assertOk();
        $response->assertDontSee($outgrown->name);
    }

    public function test_part_purchase_rejects_above_block_level(): void
    {
        $user = $this->tuningReadyUser();
        $user->playerProfile()->update(['level' => 11, 'cash' => 50000]);

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();

        $response = $this->actingAs($user)->post(route('tuning.purchase', $partModel));

        $response->assertSessionHasErrors('part_model');
        $this->assertSame(0, Part::query()->where('user_id', $user->id)->count());
    }
}
