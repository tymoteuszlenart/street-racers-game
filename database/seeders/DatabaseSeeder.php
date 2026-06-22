<?php

namespace Database\Seeders;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CarModelSeeder::class,
            PartModelSeeder::class,
            RaceSeeder::class,
            ClubTournamentSeeder::class,
            ShopProductSeeder::class,
        ]);

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])->getAttributes(),
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@street-racers.test'],
            [
                'name' => 'Admin',
                'email' => 'admin@street-racers.test',
                'password' => Hash::make('admin1234'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $maxXp = (int) (50 * ((100 * 101 * 201 / 6) - 1)); // cumulative XP for level 100

        PlayerProfile::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'cash' => 999_999_999,
                'level' => 100,
                'experience' => $maxXp,
                'fuel_current' => 100,
                'fuel_max' => 100,
                'premium_fuel_current' => 10,
                'premium_fuel_max' => 10,
            ],
        );
    }
}
