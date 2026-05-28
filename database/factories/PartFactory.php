<?php

namespace Database\Factories;

use App\Enums\PartAcquiredVia;
use App\Models\Part;
use App\Models\PartModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    protected $model = Part::class;

    public function definition(): array
    {
        $partModel = PartModel::factory()->create();

        return [
            'user_id' => User::factory(),
            'part_model_id' => $partModel->id,
            'car_id' => null,
            'slot' => $partModel->slot,
            'acquired_via' => PartAcquiredVia::Shop,
            'purchase_price' => $partModel->price,
        ];
    }
}
