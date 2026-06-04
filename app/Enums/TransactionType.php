<?php

namespace App\Enums;

enum TransactionType: string
{
    case NpcRace = 'npc_race';
    case PartPurchase = 'part_purchase';
    case PartUpgrade = 'part_upgrade';
    case CarRepair = 'car_repair';
    case PartRepair = 'part_repair';
    case CarSale = 'car_sale';
    case PartSale = 'part_sale';
    case DailyReward = 'daily_reward';
    case PremiumFuelClaim = 'premium_fuel_claim';
    case ClubTournamentEntry = 'club_tournament_entry';
    case ClubTournamentReward = 'club_tournament_reward';
    case PaidFuelPurchase = 'paid_fuel_purchase';
    case PaidPremiumFuelPurchase = 'paid_premium_fuel_purchase';
}
