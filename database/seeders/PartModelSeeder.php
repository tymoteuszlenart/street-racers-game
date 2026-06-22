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
            // ────────────────────────────────────────────────────────────────
            // ENGINE
            // ────────────────────────────────────────────────────────────────
            PartSlot::Engine->value => [
                ['name' => 'Stock Inline',          'rarity' => PartRarity::Stock,   'power_bonus' => 2,  'acceleration_bonus' => 2,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 400,    'unlock_level' => 1,  'min_car_class' => CarClass::D],
                ['name' => 'Torque Four',            'rarity' => PartRarity::Stock,   'power_bonus' => 4,  'acceleration_bonus' => 1,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 550,    'unlock_level' => 1,  'min_car_class' => CarClass::D],
                ['name' => 'Revvy I4',               'rarity' => PartRarity::Stock,   'power_bonus' => 1,  'acceleration_bonus' => 4,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 550,    'unlock_level' => 1,  'min_car_class' => CarClass::D],
                ['name' => 'Street Block',           'rarity' => PartRarity::Street,  'power_bonus' => 5,  'acceleration_bonus' => 3,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 1200,   'unlock_level' => 5,  'min_car_class' => CarClass::D],
                ['name' => 'Forged V8',              'rarity' => PartRarity::Sport,   'power_bonus' => 9,  'acceleration_bonus' => 5,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 4500,   'unlock_level' => 8,  'min_car_class' => CarClass::C],
                ['name' => 'Competition Mill',       'rarity' => PartRarity::Racing,  'power_bonus' => 14, 'acceleration_bonus' => 7,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 12000,  'unlock_level' => 12, 'min_car_class' => CarClass::B],
                ['name' => 'Stroker Kit',            'rarity' => PartRarity::Sport,   'power_bonus' => 11, 'acceleration_bonus' => 6,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 6000,   'unlock_level' => 14, 'min_car_class' => CarClass::B],
                ['name' => 'High-Flow Build',        'rarity' => PartRarity::Racing,  'power_bonus' => 16, 'acceleration_bonus' => 9,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 15000,  'unlock_level' => 18, 'min_car_class' => CarClass::B],
                ['name' => 'Rebuilt Race Block',     'rarity' => PartRarity::Racing,  'power_bonus' => 18, 'acceleration_bonus' => 10, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 22000,  'unlock_level' => 23, 'min_car_class' => CarClass::A],
                ['name' => 'Pro Engine Build',       'rarity' => PartRarity::Pro,     'power_bonus' => 20, 'acceleration_bonus' => 11, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 38000,  'unlock_level' => 30, 'min_car_class' => CarClass::A],
                ['name' => 'Stage 3 Build',          'rarity' => PartRarity::Pro,     'power_bonus' => 22, 'acceleration_bonus' => 12, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 65000,  'unlock_level' => 40, 'min_car_class' => CarClass::A],
                ['name' => 'Competition Build',      'rarity' => PartRarity::Elite,   'power_bonus' => 24, 'acceleration_bonus' => 13, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 110000, 'unlock_level' => 55, 'min_car_class' => CarClass::S],
                ['name' => 'Factory Race Spec',      'rarity' => PartRarity::Elite,   'power_bonus' => 26, 'acceleration_bonus' => 14, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 180000, 'unlock_level' => 70, 'min_car_class' => CarClass::S],
                ['name' => 'Full Race Engine',       'rarity' => PartRarity::Elite,   'power_bonus' => 28, 'acceleration_bonus' => 15, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 280000, 'unlock_level' => 90, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // TURBO
            // ────────────────────────────────────────────────────────────────
            PartSlot::Turbo->value => [
                ['name' => 'Basic Boost',            'rarity' => PartRarity::Street,  'power_bonus' => 3,  'acceleration_bonus' => 3,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 1500,   'unlock_level' => 5,  'min_car_class' => CarClass::D],
                ['name' => 'Twin Scroll',            'rarity' => PartRarity::Sport,   'power_bonus' => 6,  'acceleration_bonus' => 6,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 5200,   'unlock_level' => 9,  'min_car_class' => CarClass::C],
                ['name' => 'Hybrid Turbo',           'rarity' => PartRarity::Sport,   'power_bonus' => 8,  'acceleration_bonus' => 8,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 7000,   'unlock_level' => 13, 'min_car_class' => CarClass::B],
                ['name' => 'Big Single',             'rarity' => PartRarity::Racing,  'power_bonus' => 11, 'acceleration_bonus' => 10, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 18000,  'unlock_level' => 18, 'min_car_class' => CarClass::B],
                ['name' => 'Sequential Kit',         'rarity' => PartRarity::Racing,  'power_bonus' => 13, 'acceleration_bonus' => 12, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 30000,  'unlock_level' => 25, 'min_car_class' => CarClass::A],
                ['name' => 'Anti-Lag System',        'rarity' => PartRarity::Pro,     'power_bonus' => 15, 'acceleration_bonus' => 14, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 55000,  'unlock_level' => 35, 'min_car_class' => CarClass::A],
                ['name' => 'Race Turbo',             'rarity' => PartRarity::Pro,     'power_bonus' => 17, 'acceleration_bonus' => 16, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 90000,  'unlock_level' => 48, 'min_car_class' => CarClass::A],
                ['name' => 'Compound Boost',         'rarity' => PartRarity::Elite,   'power_bonus' => 20, 'acceleration_bonus' => 19, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 150000, 'unlock_level' => 65, 'min_car_class' => CarClass::S],
                ['name' => 'Unlimited Boost',        'rarity' => PartRarity::Elite,   'power_bonus' => 23, 'acceleration_bonus' => 22, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 250000, 'unlock_level' => 85, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // TIRES
            // ────────────────────────────────────────────────────────────────
            PartSlot::Tires->value => [
                ['name' => 'Street Tread',           'rarity' => PartRarity::Stock,   'power_bonus' => 0, 'acceleration_bonus' => 1,  'grip_bonus' => 3,  'handling_bonus' => 2,  'price' => 700,    'unlock_level' => 3,  'min_car_class' => CarClass::D],
                ['name' => 'All-Season Rubber',      'rarity' => PartRarity::Stock,   'power_bonus' => 0, 'acceleration_bonus' => 1,  'grip_bonus' => 4,  'handling_bonus' => 2,  'price' => 800,    'unlock_level' => 5,  'min_car_class' => CarClass::D],
                ['name' => 'Semi-Slick Set',         'rarity' => PartRarity::Sport,   'power_bonus' => 0, 'acceleration_bonus' => 2,  'grip_bonus' => 8,  'handling_bonus' => 4,  'price' => 3800,   'unlock_level' => 7,  'min_car_class' => CarClass::D],
                ['name' => 'Track Compound',         'rarity' => PartRarity::Racing,  'power_bonus' => 0, 'acceleration_bonus' => 3,  'grip_bonus' => 12, 'handling_bonus' => 6,  'price' => 9500,   'unlock_level' => 11, 'min_car_class' => CarClass::B],
                ['name' => 'Slick Compound',         'rarity' => PartRarity::Racing,  'power_bonus' => 0, 'acceleration_bonus' => 3,  'grip_bonus' => 15, 'handling_bonus' => 8,  'price' => 12000,  'unlock_level' => 15, 'min_car_class' => CarClass::B],
                ['name' => 'Race Slick',             'rarity' => PartRarity::Pro,     'power_bonus' => 0, 'acceleration_bonus' => 4,  'grip_bonus' => 18, 'handling_bonus' => 10, 'price' => 28000,  'unlock_level' => 22, 'min_car_class' => CarClass::A],
                ['name' => 'Qualifier Set',          'rarity' => PartRarity::Pro,     'power_bonus' => 0, 'acceleration_bonus' => 4,  'grip_bonus' => 20, 'handling_bonus' => 11, 'price' => 55000,  'unlock_level' => 35, 'min_car_class' => CarClass::A],
                ['name' => 'Endurance Compound',     'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 5,  'grip_bonus' => 22, 'handling_bonus' => 12, 'price' => 95000,  'unlock_level' => 50, 'min_car_class' => CarClass::S],
                ['name' => 'Sprint Slick',           'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 5,  'grip_bonus' => 24, 'handling_bonus' => 13, 'price' => 160000, 'unlock_level' => 70, 'min_car_class' => CarClass::S],
                ['name' => 'Time Attack Slick',      'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 6,  'grip_bonus' => 26, 'handling_bonus' => 14, 'price' => 260000, 'unlock_level' => 88, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // SUSPENSION
            // ────────────────────────────────────────────────────────────────
            PartSlot::Suspension->value => [
                ['name' => 'Factory Springs',        'rarity' => PartRarity::Stock,   'power_bonus' => 0, 'acceleration_bonus' => 0, 'grip_bonus' => 1,  'handling_bonus' => 3,  'price' => 650,    'unlock_level' => 2,  'min_car_class' => CarClass::D],
                ['name' => 'Lowering Kit',           'rarity' => PartRarity::Street,  'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 2,  'handling_bonus' => 5,  'price' => 1100,   'unlock_level' => 5,  'min_car_class' => CarClass::D],
                ['name' => 'Coilover Pro',           'rarity' => PartRarity::Sport,   'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 4,  'handling_bonus' => 9,  'price' => 4200,   'unlock_level' => 8,  'min_car_class' => CarClass::C],
                ['name' => 'Motorsport Kit',         'rarity' => PartRarity::Racing,  'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 6,  'handling_bonus' => 12, 'price' => 7500,   'unlock_level' => 12, 'min_car_class' => CarClass::B],
                ['name' => 'Race Dampers',           'rarity' => PartRarity::Racing,  'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 8,  'handling_bonus' => 14, 'price' => 16000,  'unlock_level' => 18, 'min_car_class' => CarClass::B],
                ['name' => 'Full Aero Kit',          'rarity' => PartRarity::Pro,     'power_bonus' => 0, 'acceleration_bonus' => 3, 'grip_bonus' => 10, 'handling_bonus' => 17, 'price' => 35000,  'unlock_level' => 28, 'min_car_class' => CarClass::A],
                ['name' => 'Track Setup',            'rarity' => PartRarity::Pro,     'power_bonus' => 0, 'acceleration_bonus' => 3, 'grip_bonus' => 12, 'handling_bonus' => 19, 'price' => 62000,  'unlock_level' => 40, 'min_car_class' => CarClass::A],
                ['name' => 'Race Geometry',          'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 4, 'grip_bonus' => 14, 'handling_bonus' => 22, 'price' => 105000, 'unlock_level' => 56, 'min_car_class' => CarClass::S],
                ['name' => 'Full Race Chassis',      'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 4, 'grip_bonus' => 16, 'handling_bonus' => 24, 'price' => 175000, 'unlock_level' => 75, 'min_car_class' => CarClass::S],
                ['name' => 'Carbon Race Chassis',    'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 5, 'grip_bonus' => 18, 'handling_bonus' => 26, 'price' => 280000, 'unlock_level' => 92, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // GEARBOX
            // ────────────────────────────────────────────────────────────────
            PartSlot::Gearbox->value => [
                ['name' => 'Short Throw',            'rarity' => PartRarity::Street,  'power_bonus' => 1, 'acceleration_bonus' => 4,  'grip_bonus' => 0, 'handling_bonus' => 1, 'price' => 1300,   'unlock_level' => 6,  'min_car_class' => CarClass::D],
                ['name' => 'Close Ratio',            'rarity' => PartRarity::Sport,   'power_bonus' => 2, 'acceleration_bonus' => 8,  'grip_bonus' => 0, 'handling_bonus' => 2, 'price' => 4800,   'unlock_level' => 9,  'min_car_class' => CarClass::C],
                ['name' => 'Sequential Box',         'rarity' => PartRarity::Racing,  'power_bonus' => 3, 'acceleration_bonus' => 11, 'grip_bonus' => 0, 'handling_bonus' => 2, 'price' => 9000,   'unlock_level' => 14, 'min_car_class' => CarClass::B],
                ['name' => 'Dog Engagement',         'rarity' => PartRarity::Racing,  'power_bonus' => 4, 'acceleration_bonus' => 13, 'grip_bonus' => 0, 'handling_bonus' => 3, 'price' => 20000,  'unlock_level' => 20, 'min_car_class' => CarClass::B],
                ['name' => 'Straight-Cut Box',       'rarity' => PartRarity::Pro,     'power_bonus' => 5, 'acceleration_bonus' => 16, 'grip_bonus' => 0, 'handling_bonus' => 3, 'price' => 42000,  'unlock_level' => 30, 'min_car_class' => CarClass::A],
                ['name' => 'Race Sequential',        'rarity' => PartRarity::Pro,     'power_bonus' => 6, 'acceleration_bonus' => 18, 'grip_bonus' => 0, 'handling_bonus' => 4, 'price' => 78000,  'unlock_level' => 45, 'min_car_class' => CarClass::A],
                ['name' => 'PDK Race Unit',          'rarity' => PartRarity::Elite,   'power_bonus' => 7, 'acceleration_bonus' => 21, 'grip_bonus' => 0, 'handling_bonus' => 4, 'price' => 130000, 'unlock_level' => 62, 'min_car_class' => CarClass::S],
                ['name' => 'Paddle Shift Race',      'rarity' => PartRarity::Elite,   'power_bonus' => 8, 'acceleration_bonus' => 24, 'grip_bonus' => 0, 'handling_bonus' => 5, 'price' => 220000, 'unlock_level' => 80, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // BRAKES
            // ────────────────────────────────────────────────────────────────
            PartSlot::Brakes->value => [
                ['name' => 'OEM Discs',              'rarity' => PartRarity::Stock,   'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 2,  'handling_bonus' => 2,  'price' => 350,    'unlock_level' => 1,  'min_car_class' => CarClass::D],
                ['name' => 'Sport Pads',             'rarity' => PartRarity::Stock,   'power_bonus' => 0, 'acceleration_bonus' => 1, 'grip_bonus' => 1,  'handling_bonus' => 4,  'price' => 500,    'unlock_level' => 1,  'min_car_class' => CarClass::D],
                ['name' => 'Slotted Rotors',         'rarity' => PartRarity::Street,  'power_bonus' => 0, 'acceleration_bonus' => 2, 'grip_bonus' => 3,  'handling_bonus' => 3,  'price' => 1000,   'unlock_level' => 5,  'min_car_class' => CarClass::D],
                ['name' => 'Big Brake Kit',          'rarity' => PartRarity::Racing,  'power_bonus' => 0, 'acceleration_bonus' => 3, 'grip_bonus' => 6,  'handling_bonus' => 6,  'price' => 6000,   'unlock_level' => 10, 'min_car_class' => CarClass::C],
                ['name' => 'Carbon Ceramics',        'rarity' => PartRarity::Pro,     'power_bonus' => 0, 'acceleration_bonus' => 4, 'grip_bonus' => 9,  'handling_bonus' => 9,  'price' => 10000,  'unlock_level' => 15, 'min_car_class' => CarClass::B],
                ['name' => 'Race Calipers',          'rarity' => PartRarity::Pro,     'power_bonus' => 0, 'acceleration_bonus' => 5, 'grip_bonus' => 11, 'handling_bonus' => 11, 'price' => 22000,  'unlock_level' => 22, 'min_car_class' => CarClass::A],
                ['name' => 'Endurance Brakes',       'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 5, 'grip_bonus' => 13, 'handling_bonus' => 13, 'price' => 48000,  'unlock_level' => 35, 'min_car_class' => CarClass::A],
                ['name' => 'Sprint Brakes',          'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 6, 'grip_bonus' => 15, 'handling_bonus' => 15, 'price' => 88000,  'unlock_level' => 52, 'min_car_class' => CarClass::S],
                ['name' => 'Full Race Brakes',       'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 7, 'grip_bonus' => 17, 'handling_bonus' => 17, 'price' => 155000, 'unlock_level' => 72, 'min_car_class' => CarClass::S],
                ['name' => 'Carbon Race System',     'rarity' => PartRarity::Elite,   'power_bonus' => 0, 'acceleration_bonus' => 8, 'grip_bonus' => 20, 'handling_bonus' => 20, 'price' => 250000, 'unlock_level' => 90, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // NITROUS
            // ────────────────────────────────────────────────────────────────
            PartSlot::Nitrous->value => [
                ['name' => 'Dry Shot',               'rarity' => PartRarity::Street,  'power_bonus' => 5,  'acceleration_bonus' => 5,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 2000,   'unlock_level' => 8,  'min_car_class' => CarClass::D],
                ['name' => 'Wet Shot',               'rarity' => PartRarity::Sport,   'power_bonus' => 8,  'acceleration_bonus' => 8,  'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 3500,   'unlock_level' => 13, 'min_car_class' => CarClass::B],
                ['name' => 'Progressive Kit',        'rarity' => PartRarity::Pro,     'power_bonus' => 11, 'acceleration_bonus' => 11, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 7500,   'unlock_level' => 11, 'min_car_class' => CarClass::B],
                ['name' => 'Stage 2 Kit',            'rarity' => PartRarity::Racing,  'power_bonus' => 13, 'acceleration_bonus' => 13, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 14000,  'unlock_level' => 18, 'min_car_class' => CarClass::B],
                ['name' => 'Twin Bottle',            'rarity' => PartRarity::Racing,  'power_bonus' => 15, 'acceleration_bonus' => 15, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 25000,  'unlock_level' => 25, 'min_car_class' => CarClass::A],
                ['name' => 'Purge Kit',              'rarity' => PartRarity::Pro,     'power_bonus' => 17, 'acceleration_bonus' => 17, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 48000,  'unlock_level' => 35, 'min_car_class' => CarClass::A],
                ['name' => 'Direct Port',            'rarity' => PartRarity::Pro,     'power_bonus' => 19, 'acceleration_bonus' => 19, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 85000,  'unlock_level' => 50, 'min_car_class' => CarClass::A],
                ['name' => 'Full Stage NOS',         'rarity' => PartRarity::Elite,   'power_bonus' => 22, 'acceleration_bonus' => 22, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 140000, 'unlock_level' => 68, 'min_car_class' => CarClass::S],
                ['name' => 'Competition NOS',        'rarity' => PartRarity::Elite,   'power_bonus' => 25, 'acceleration_bonus' => 25, 'grip_bonus' => 0, 'handling_bonus' => 0, 'price' => 230000, 'unlock_level' => 88, 'min_car_class' => CarClass::S],
            ],

            // ────────────────────────────────────────────────────────────────
            // ECU
            // ────────────────────────────────────────────────────────────────
            PartSlot::Ecu->value => [
                ['name' => 'Stage 1 Tune',           'rarity' => PartRarity::Street,  'power_bonus' => 2,  'acceleration_bonus' => 3,  'grip_bonus' => 0, 'handling_bonus' => 1, 'price' => 900,    'unlock_level' => 4,  'min_car_class' => CarClass::D],
                ['name' => 'Custom Map',             'rarity' => PartRarity::Sport,   'power_bonus' => 4,  'acceleration_bonus' => 6,  'grip_bonus' => 0, 'handling_bonus' => 2, 'price' => 3500,   'unlock_level' => 8,  'min_car_class' => CarClass::D],
                ['name' => 'Dyno Pro File',          'rarity' => PartRarity::Elite,   'power_bonus' => 7,  'acceleration_bonus' => 10, 'grip_bonus' => 1, 'handling_bonus' => 3, 'price' => 11000,  'unlock_level' => 13, 'min_car_class' => CarClass::A],
                ['name' => 'Stage 2 Tune',           'rarity' => PartRarity::Sport,   'power_bonus' => 6,  'acceleration_bonus' => 9,  'grip_bonus' => 1, 'handling_bonus' => 3, 'price' => 5500,   'unlock_level' => 16, 'min_car_class' => CarClass::B],
                ['name' => 'Race Tune',              'rarity' => PartRarity::Racing,  'power_bonus' => 9,  'acceleration_bonus' => 13, 'grip_bonus' => 2, 'handling_bonus' => 4, 'price' => 18000,  'unlock_level' => 22, 'min_car_class' => CarClass::B],
                ['name' => 'Full Engine Map',        'rarity' => PartRarity::Racing,  'power_bonus' => 11, 'acceleration_bonus' => 15, 'grip_bonus' => 2, 'handling_bonus' => 5, 'price' => 35000,  'unlock_level' => 30, 'min_car_class' => CarClass::A],
                ['name' => 'Launch Control',         'rarity' => PartRarity::Pro,     'power_bonus' => 13, 'acceleration_bonus' => 18, 'grip_bonus' => 3, 'handling_bonus' => 6, 'price' => 68000,  'unlock_level' => 42, 'min_car_class' => CarClass::A],
                ['name' => 'Traction Map',           'rarity' => PartRarity::Pro,     'power_bonus' => 15, 'acceleration_bonus' => 20, 'grip_bonus' => 4, 'handling_bonus' => 7, 'price' => 115000, 'unlock_level' => 58, 'min_car_class' => CarClass::S],
                ['name' => 'Full Race ECU',          'rarity' => PartRarity::Elite,   'power_bonus' => 17, 'acceleration_bonus' => 22, 'grip_bonus' => 5, 'handling_bonus' => 8, 'price' => 190000, 'unlock_level' => 76, 'min_car_class' => CarClass::S],
                ['name' => 'Unlimited ECU',          'rarity' => PartRarity::Elite,   'power_bonus' => 20, 'acceleration_bonus' => 25, 'grip_bonus' => 6, 'handling_bonus' => 9, 'price' => 300000, 'unlock_level' => 95, 'min_car_class' => CarClass::S],
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
