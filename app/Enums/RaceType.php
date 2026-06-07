<?php

namespace App\Enums;

enum RaceType: string
{
    case Drag = 'drag';
    case Sprint = 'sprint';
    case Drift = 'drift';
    case Circuit = 'circuit';

    public function label(): string
    {
        return match ($this) {
            self::Drag => __('Drag'),
            self::Sprint => __('Sprint'),
            self::Drift => __('Drift'),
            self::Circuit => __('Circuit'),
        };
    }

    /**
     * @return list<string>
     */
    public function favoredStatKeys(): array
    {
        $affinities = config("game.player.driver_stats.race_type_affinities.{$this->value}", []);
        $sorted = collect($affinities)->sortDesc();

        return $sorted->take(2)->keys()->all();
    }
}
