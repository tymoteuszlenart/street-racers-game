<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

final class GameDay
{
    public static function timezone(): string
    {
        return (string) config('app.timezone');
    }

    public static function now(): Carbon
    {
        return now(self::timezone());
    }

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::now()->toDateString(), self::timezone());
    }

    /**
     * Inclusive UTC bounds for the calendar day of $instant in the app timezone.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bounds(?Carbon $instant = null): array
    {
        $instant ??= self::now();

        return [
            $instant->copy()->startOfDay()->utc(),
            $instant->copy()->endOfDay()->utc(),
        ];
    }
}
