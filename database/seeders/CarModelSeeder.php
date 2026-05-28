<?php

namespace Database\Seeders;

use App\Enums\CarClass;
use App\Enums\PartSlot;
use App\Models\CarModel;
use Illuminate\Database\Seeder;

class CarModelSeeder extends Seeder
{
    public function run(): void
    {
        $upgradeSlots = PartSlot::values();

        $models = [
            [
                'name' => 'Rust Runner',
                'class' => CarClass::D,
                'rarity' => 'common',
                'image_path' => 'cars/rust-runner.svg',
                'power' => 42,
                'acceleration' => 48,
                'weight' => 55,
                'grip' => 45,
                'handling' => 44,
                'durability' => 50,
                'upgrade_slots' => $upgradeSlots,
                'price' => 0,
                'starter' => true,
                'unlock_level' => 1,
                'active' => true,
            ],
            [
                'name' => 'Neon Hatch',
                'class' => CarClass::D,
                'rarity' => 'common',
                'image_path' => 'cars/neon-hatch.svg',
                'power' => 50,
                'acceleration' => 52,
                'weight' => 48,
                'grip' => 50,
                'handling' => 51,
                'durability' => 46,
                'upgrade_slots' => $upgradeSlots,
                'price' => 3500,
                'starter' => false,
                'unlock_level' => 1,
                'active' => true,
            ],
            [
                'name' => 'Midnight Coupe',
                'class' => CarClass::C,
                'rarity' => 'uncommon',
                'image_path' => 'cars/midnight-coupe.svg',
                'power' => 62,
                'acceleration' => 60,
                'weight' => 52,
                'grip' => 58,
                'handling' => 57,
                'durability' => 54,
                'upgrade_slots' => $upgradeSlots,
                'price' => 12000,
                'starter' => false,
                'unlock_level' => 3,
                'active' => true,
            ],
            [
                'name' => 'Voltage GT',
                'class' => CarClass::B,
                'rarity' => 'rare',
                'image_path' => 'cars/voltage-gt.svg',
                'power' => 78,
                'acceleration' => 76,
                'weight' => 46,
                'grip' => 72,
                'handling' => 74,
                'durability' => 60,
                'upgrade_slots' => $upgradeSlots,
                'price' => 45000,
                'starter' => false,
                'unlock_level' => 8,
                'active' => true,
            ],
        ];

        foreach ($models as $model) {
            CarModel::query()->updateOrCreate(
                ['name' => $model['name']],
                $model,
            );
        }
    }
}
