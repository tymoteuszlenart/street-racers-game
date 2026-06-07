<?php

namespace App\Enums;

enum RaceTier: string
{
    case Amateur = 'amateur';
    case SemiPro = 'semi_pro';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Amateur => __('Amateur'),
            self::SemiPro => __('Semi-Pro'),
            self::Pro => __('Pro'),
        };
    }

    public function difficultyLabel(): string
    {
        return match ($this) {
            self::Amateur => __('Easy'),
            self::SemiPro => __('Medium'),
            self::Pro => __('Hard'),
        };
    }

    public function configKey(): string
    {
        return match ($this) {
            self::Amateur => 'Amateur',
            self::SemiPro => 'Semi-Pro',
            self::Pro => 'Pro',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Amateur => 1,
            self::SemiPro => 2,
            self::Pro => 3,
        };
    }
}
