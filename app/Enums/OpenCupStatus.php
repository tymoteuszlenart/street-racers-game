<?php

namespace App\Enums;

enum OpenCupStatus: string
{
    case Open = 'open';
    case Settling = 'settling';
    case Running = 'running';
    case Completed = 'completed';

    /**
     * @return list<self>
     */
    public static function activeForPlayer(): array
    {
        return [
            self::Open,
            self::Settling,
            self::Running,
        ];
    }
}
