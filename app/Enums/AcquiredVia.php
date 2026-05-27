<?php

namespace App\Enums;

enum AcquiredVia: string
{
    case Starter = 'starter';
    case Dealer = 'dealer';
    case Admin = 'admin';
    case Reward = 'reward';
}
