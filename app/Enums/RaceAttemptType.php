<?php

namespace App\Enums;

enum RaceAttemptType: string
{
    case Npc = 'npc';
    case Pvp = 'pvp';
    case ClubTournament = 'club_tournament';
    case OpenCup = 'open_cup';
}
