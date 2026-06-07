<?php

namespace Database\Seeders;

use App\Enums\CarClass;
use App\Enums\PartRarity;
use App\Enums\PartSlot;
use App\Models\PartModel;
use Illuminate\Database\Seeder;

class PartModelSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            PartSlot::Engine->value => [
                ['name' => 'Stock Inline', 'rarity' => PartRarity::Stock, 'power_bonus' => 2, 'acceleration_bonus' => 2, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 400, 'unlock_level' => 1, 'min_car_class' => CarClass::D],
                ['name' => 'Torque Four', 'rarity' => PartRarity::Stock, 'power_bonus' => 4, 'acceleration_bonus' => 1, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 550, 'unlock_level' => 1, 'min_car_class' => CarClass::D],
                ['name' => 'Revvy I4', 'rarity' => PartRarity::Stock, 'power_bonus' => 1, 'acceleration_bonus' => 4, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 550, 'unlock_level' => 1, 'min_car_class' => CarClass::D],
                ['name' => 'Street Block', 'rarity' => PartRarity::Street, 'power_bonus' => 4, 'acceleration_bonus' => 2, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 1200, 'unlock_level' => 5, 'min_car_class' => CarClass::D],
                ['name' => 'Forged V8', 'rarity' => PartRarity::Sport, 'power_bonus' => 8, 'acceleration_bonus' => 4, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 4500, 'unlock_level' => 8, 'min_car_class' => CarClass::C],
                ['name' => 'Competition Mill', 'rarity' => PartRarity::Racing, 'power_bonus' => 14, 'acceleration_bonus' => 6, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 12000, 'unlock_level' => 12, 'min_car_class' => CarClass::B],
            ],
            PartSlot::Turbo->value => [
                ['name' => 'Basic Boost', 'rarity' => PartRarity::Street, 'power_bonus' => 3, 'acceleration_bonus' => 3, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 1500, 'unlock_level' => 5, 'min_car_class' => CarClass::D],
                ['name' => 'Twin Scroll', 'rarity' => PartRarity::Sport, 'power_bonus' => 6, 'acceleration_bonus' => 6, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 5200, 'unlock_level' => 9, 'min_car_class' => CarClass::C],
            ],
            PartSlot::Tires->value => [
                ['name' => 'Street Tread', 'rarity' => PartRarity::Stock, 'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 3, 'handling_bonus' => 2, 'price' => 700, 'unlock_level' => 3, 'min_car_class' => CarClass::D],
                ['name' => 'All-Season Rubber', 'rarity' => PartRarity::Stock, 'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 4, 'handling_bonus' => 2, 'price' => 800, 'unlock_level' => 5, 'min_car_class' => CarClass::D],
                ['name' => 'Semi-Slick Set', 'rarity' => PartRarity::Sport, 'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 8, 'handling_bonus' => 4, 'price' => 3800, 'unlock_level' => 7, 'min_car_class' => CarClass::D],
                ['name' => 'Track Compound', 'rarity' => PartRarity::Racing, 'power_bonus' => 0, 'acceleration_bonus' => 3, 'grip_bonus' => 12, 'handling_bonus' => 6, 'price' => 9500, 'unlock_level' => 11, 'min_car_class' => CarClass::B],
            ],
            PartSlot::Suspension->value => [
                ['name' => 'Factory Springs', 'rarity' => PartRarity::Stock, 'power_bonus' => 0, 'acceleration_bonus' => 0, 'grip_bonus' => 1, 'handling_bonus' => 3, 'price' => 650, 'unlock_level' => 2, 'min_car_class' => CarClass::D],
                ['name' => 'Lowering Kit', 'rarity' => PartRarity::Street, 'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 2, 'handling_bonus' => 5, 'price' => 1100, 'unlock_level' => 5, 'min_car_class' => CarClass::D],
                ['name' => 'Coilover Pro', 'rarity' => PartRarity::Sport, 'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 4, 'handling_bonus' => 9, 'price' => 4200, 'unlock_level' => 8, 'min_car_class' => CarClass::C],
            ],
            PartSlot::Gearbox->value => [
                ['name' => 'Short Throw', 'rarity' => PartRarity::Street, 'power_bonus' => 1, 'acceleration_bonus' => 4, 'grip_bonus' => 0, 'handling_bonus' => 1, 'price' => 1300, 'unlock_level' => 6, 'min_car_class' => CarClass::D],
                ['name' => 'Close Ratio', 'rarity' => PartRarity::Sport, 'power_bonus' => 2, 'acceleration_bonus' => 8, 'grip_bonus' => 0, 'handling_bonus' => 2, 'price' => 4800, 'unlock_level' => 9, 'min_car_class' => CarClass::C],
            ],
            PartSlot::Brakes->value => [
                ['name' => 'OEM Discs', 'rarity' => PartRarity::Stock, 'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 2, 'handling_bonus' => 2, 'price' => 350, 'unlock_level' => 1, 'min_car_class' => CarClass::D],
                ['name' => 'Sport Pads', 'rarity' => PartRarity::Stock, 'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 1, 'handling_bonus' => 4, 'price' => 500, 'unlock_level' => 1, 'min_car_class' => CarClass::D],
                ['name' => 'Slotted Rotors', 'rarity' => PartRarity::Street, 'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 3, 'handling_bonus' => 3, 'price' => 1000, 'unlock_level' => 5, 'min_car_class' => CarClass::D],
                ['name' => 'Big Brake Kit', 'rarity' => PartRarity::Racing, 'power_bonus' => 0, 'acceleration_bonus' => 3, 'grip_bonus' => 6, 'handling_bonus' => 6, 'price' => 6000, 'unlock_level' => 10, 'min_car_class' => CarClass::C],
            ],
            PartSlot::Nitrous->value => [
                ['name' => 'Dry Shot', 'rarity' => PartRarity::Street, 'power_bonus' => 5, 'acceleration_bonus' => 5, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 2000, 'unlock_level' => 8, 'min_car_class' => CarClass::D],
                ['name' => 'Progressive Kit', 'rarity' => PartRarity::Pro, 'power_bonus' => 10, 'acceleration_bonus' => 10, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 7500, 'unlock_level' => 11, 'min_car_class' => CarClass::B],
            ],
            PartSlot::Ecu->value => [
                ['name' => 'Stage 1 Tune', 'rarity' => PartRarity::Street, 'power_bonus' => 2, 'acceleration_bonus' => 3, 'grip_bonus' => 0, 'handling_bonus' => 1, 'price' => 900, 'unlock_level' => 4, 'min_car_class' => CarClass::D],
                ['name' => 'Custom Map', 'rarity' => PartRarity::Sport, 'power_bonus' => 4, 'acceleration_bonus' => 6, 'grip_bonus' => 0, 'handling_bonus' => 2, 'price' => 3500, 'unlock_level' => 8, 'min_car_class' => CarClass::D],
                ['name' => 'Dyno Pro File', 'rarity' => PartRarity::Elite, 'power_bonus' => 7, 'acceleration_bonus' => 10, 'grip_bonus' => 0, 'handling_bonus' => 3, 'price' => 11000, 'unlock_level' => 13, 'min_car_class' => CarClass::A],
            ],
        ];

        foreach ($catalog as $slot => $parts) {
            foreach ($parts as $part) {
                PartModel::query()->updateOrCreate(
                    ['name' => $part['name']],
                    [
                        'slot' => $slot,
                        'rarity' => $part['rarity'],
                        'image_path' => null,
                        'power_bonus' => $part['power_bonus'],
                        'acceleration_bonus' => $part['acceleration_bonus'],
                        'grip_bonus' => $part['grip_bonus'],
                        'handling_bonus' => $part['handling_bonus'],
                        'price' => $part['price'],
                        'unlock_level' => $part['unlock_level'],
                        'block_level' => $part['unlock_level'] + 5,
                        'min_car_class' => $part['min_car_class'],
                        'active' => true,
                    ],
                );
            }
        }
    }
}
