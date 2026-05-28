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

    public function test_tuning_shop_index_forbidden_below_level_five(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tuning.index'))->assertForbidden();
    }

    public function test_tuning_shop_index_available_at_level_five(): void
    {
        $user = $this->tuningReadyUser();

        $this->actingAs($user)->get(route('tuning.index'))->assertOk();
    }

    public function test_part_purchase_deducts_cash_and_creates_inventory_row(): void
    {
        $user = $this->tuningReadyUser();
        $profile = $user->playerProfile()->firstOrFail();

        $partModel = PartModel::query()->where('name', 'Street Block')->firstOrFail();
        $expectedCash = $profile->cash - $partModel->price;

        $response = $this->actingAs($user)->post(route('tuning.purchase', $partModel));

        $response->assertRedirect(route('tuning.index'));
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
}
