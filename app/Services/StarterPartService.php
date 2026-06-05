<?php

namespace App\Services;

use App\Enums\PartAcquiredVia;
use App\Enums\PartSlot;
use App\Exceptions\StarterPartCatalogNotConfiguredException;
use App\Models\Car;
use App\Models\Part;
use App\Models\PartModel;
use Illuminate\Support\Facades\Log;

class StarterPartService
{
    public function attachToCar(Car $car): void
    {
        foreach ([PartSlot::Engine, PartSlot::Brakes] as $slot) {
            $this->attachSlot($car, $slot);
        }
    }

    private function attachSlot(Car $car, PartSlot $slot): void
    {
        $partModel = $this->resolvePartModel($slot);

        Part::query()->create([
            'user_id' => $car->user_id,
            'part_model_id' => $partModel->id,
            'car_id' => $car->id,
            'slot' => $slot,
            'acquired_via' => PartAcquiredVia::Starter,
            'purchase_price' => null,
        ]);
    }

    private function resolvePartModel(PartSlot $slot): PartModel
    {
        $name = config("game.starter_parts.{$slot->value}");

        if (! is_string($name) || $name === '') {
            Log::error('Starter part catalog is not configured.', [
                'slot' => $slot->value,
            ]);

            throw new StarterPartCatalogNotConfiguredException;
        }

        $partModel = PartModel::query()
            ->active()
            ->where('slot', $slot)
            ->where('name', $name)
            ->first();

        if ($partModel === null) {
            Log::error('Starter part model not found in catalog.', [
                'slot' => $slot->value,
                'name' => $name,
            ]);

            throw new StarterPartCatalogNotConfiguredException;
        }

        return $partModel;
    }
}
