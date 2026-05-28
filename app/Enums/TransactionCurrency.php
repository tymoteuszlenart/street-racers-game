<?php

namespace App\Enums;

enum TransactionCurrency: string
{
    case Fuel = 'fuel';
    case Cash = 'cash';
    case Reputation = 'reputation';
    case Experience = 'experience';
}
