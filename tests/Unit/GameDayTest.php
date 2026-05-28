<?php

namespace Tests\Unit;

use App\Support\GameDay;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GameDayTest extends TestCase
{
    public function test_bounds_cover_full_app_timezone_day_in_utc(): void
    {
        config(['app.timezone' => 'America/New_York']);

        $this->travelTo(Carbon::parse('2026-05-28 15:00:00', 'America/New_York'));

        [$start, $end] = GameDay::bounds();

        $this->assertSame('2026-05-28 04:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-29 03:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_today_matches_app_timezone_date(): void
    {
        config(['app.timezone' => 'Europe/Warsaw']);

        $this->travelTo(Carbon::parse('2026-05-28 23:30:00', 'Europe/Warsaw'));

        $this->assertSame('2026-05-28', GameDay::today()->toDateString());
    }
}
