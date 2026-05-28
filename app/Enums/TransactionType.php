<?php

namespace App\Enums;

enum TransactionType: string
{
    case NpcRace = 'npc_race';
    case PartPurchase = 'part_purchase';
    case DailyReward = 'daily_reward';
    case PremiumFuelClaim = 'premium_fuel_claim';
    case ClubTournamentEntry = 'club_tournament_entry';
    case ClubTournamentReward = 'club_tournament_reward';
}
