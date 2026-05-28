<?php

namespace App\Enums;

enum PartAcquiredVia: string
{
    case Shop = 'shop';
    case Reward = 'reward';
    case Admin = 'admin';
}
