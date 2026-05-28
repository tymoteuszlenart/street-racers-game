<?php

namespace App\Enums;

enum ClubTournamentStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case RewardsDistributed = 'rewards_distributed';
}
