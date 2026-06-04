<?php

namespace App\Enums;

enum OpenCupMatchPhase: string
{
    case Solo = 'solo';
    case Qualifying = 'qualifying';
    case Bracket = 'bracket';
}
