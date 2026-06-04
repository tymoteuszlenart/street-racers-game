<?php

namespace App\Support;

use App\Enums\PartSlot;

class PartsShopUnlock
{
    public static function shopLevel(): int
    {
        return (int) config('game.parts_shop.unlock_level', 1);
    }

    public static function slotLevel(PartSlot $slot): int
    {
        $levels = config('game.parts_shop.slot_unlock_levels', []);

        return (int) ($levels[$slot->value] ?? 1);
    }

    public static function slotUnlocked(PartSlot $slot, int $playerLevel): bool
    {
        return $playerLevel >= self::slotLevel($slot);
    }
}
