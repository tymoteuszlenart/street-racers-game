<?php

namespace App\Enums;

enum FuelGrantMode: string
{
    case Add = 'add';
    case FillToMax = 'fill_to_max';
}
