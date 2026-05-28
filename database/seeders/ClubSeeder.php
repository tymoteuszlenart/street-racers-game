<?php

namespace Database\Seeders;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $streetKings = Club::query()->firstOrCreate(
            ['slug' => 'street-kings'],
            [
                'name' => 'Street Kings',
                'description' => 'Dominating the downtown circuit.',
                'points' => 0,
                'level' => 1,
            ],
        );

        $midnightRunners = Club::query()->firstOrCreate(
            ['slug' => 'midnight-runners'],
            [
                'name' => 'Midnight Runners',
                'description' => 'Late-night crews only.',
                'points' => 0,
                'level' => 1,
            ],
        );

        $users = User::query()->limit(4)->get();

        if ($users->count() >= 1) {
            ClubMember::query()->firstOrCreate(
                ['user_id' => $users[0]->id],
                [
                    'club_id' => $streetKings->id,
                    'role' => ClubRole::Owner,
                    'joined_at' => now(),
                ],
            );
        }

        if ($users->count() >= 2) {
            ClubMember::query()->firstOrCreate(
                ['user_id' => $users[1]->id],
                [
                    'club_id' => $streetKings->id,
                    'role' => ClubRole::Member,
                    'joined_at' => now(),
                ],
            );
        }

        if ($users->count() >= 3) {
            ClubMember::query()->firstOrCreate(
                ['user_id' => $users[2]->id],
                [
                    'club_id' => $midnightRunners->id,
                    'role' => ClubRole::Owner,
                    'joined_at' => now(),
                ],
            );
        }

        if ($users->count() >= 4) {
            ClubMember::query()->firstOrCreate(
                ['user_id' => $users[3]->id],
                [
                    'club_id' => $midnightRunners->id,
                    'role' => ClubRole::Manager,
                    'joined_at' => now(),
                ],
            );
        }
    }
}
