<?php

namespace Database\Factories;

use App\Enums\ClubTournamentStatus;
use App\Models\ClubTournament;
use App\Support\GameWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubTournament>
 */
class ClubTournamentFactory extends Factory
{
    protected $model = ClubTournament::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$startsAt, $endsAt] = GameWeek::bounds();

        return [
            'season_key' => GameWeek::seasonKey(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => ClubTournamentStatus::Active,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => ClubTournamentStatus::Closed,
        ]);
    }
}
