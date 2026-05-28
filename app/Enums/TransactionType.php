<?php

namespace App\Enums;

enum TransactionType: string
{
    case NpcRace = 'npc_race';
    case PartPurchase = 'part_purchase';
    case DailyReward = 'daily_reward';
}
