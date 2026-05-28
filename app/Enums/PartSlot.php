<?php

namespace App\Enums;

enum PartSlot: string
{
    case Engine = 'engine';
    case Turbo = 'turbo';
    case Tires = 'tires';
    case Suspension = 'suspension';
    case Gearbox = 'gearbox';
    case Brakes = 'brakes';
    case Nitrous = 'nitrous';
    case Ecu = 'ecu';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
