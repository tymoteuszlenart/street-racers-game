<?php

namespace App\Enums;

enum CarClass: string
{
    case D = 'D';
    case C = 'C';
    case B = 'B';
    case A = 'A';
    case S = 'S';

    public function rank(): int
    {
        return match ($this) {
            self::D => 1,
            self::C => 2,
            self::B => 3,
            self::A => 4,
            self::S => 5,
        };
    }
}
