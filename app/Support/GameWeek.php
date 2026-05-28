<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

final class GameWeek
{
    public static function seasonKey(?Carbon $instant = null): string
    {
        $instant ??= GameDay::now();

        return $instant->copy()->startOfWeek(Carbon::MONDAY)->format('o-\WW');
    }

    /**
     * Weekly season bounds: Monday 00:00:00 through Sunday 23:59:59 (app timezone).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bounds(?Carbon $instant = null): array
    {
        $instant ??= GameDay::now();
        $weekStart = $instant->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        return [$weekStart, $weekEnd];
    }

    public static function currentStartsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::bounds()[0], GameDay::timezone());
    }

    public static function currentEndsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::bounds()[1], GameDay::timezone());
    }
}
