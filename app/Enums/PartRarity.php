<?php

namespace App\Enums;

enum PartRarity: string
{
    case Stock = 'stock';
    case Street = 'street';
    case Sport = 'sport';
    case Racing = 'racing';
    case Pro = 'pro';
    case Elite = 'elite';
    case Illegal = 'illegal';
}
