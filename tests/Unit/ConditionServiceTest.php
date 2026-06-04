<?php

namespace Tests\Unit;

use App\Services\ConditionService;
use Tests\TestCase;

class ConditionServiceTest extends TestCase
{
    private ConditionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ConditionService::class);
    }

    public function test_part_stat_factor_is_full_at_or_above_good_threshold(): void
    {
        $this->assertSame(1.0, $this->service->partStatFactor(140, 200));
        $this->assertSame(1.0, $this->service->partStatFactor(70, 100));
    }

    public function test_part_stat_factor_is_half_at_one_percent(): void
    {
        $this->assertEqualsWithDelta(0.5, $this->service->partStatFactor(2, 200), 0.01);
        $this->assertEqualsWithDelta(0.5, $this->service->partStatFactor(1, 100), 0.01);
    }

    public function test_part_stat_factor_scales_between_one_and_good_threshold(): void
    {
        $factor = $this->service->partStatFactor(100, 200);

        $this->assertGreaterThan(0.5, $factor);
        $this->assertLessThan(1.0, $factor);
    }

    public function test_car_score_penalty_uses_tier_thresholds(): void
    {
        $this->assertSame(0.0, $this->service->carScorePenaltyFromPercent(80.0));
        $this->assertSame(5.0, $this->service->carScorePenaltyFromPercent(50.0));
        $this->assertSame(8.0, $this->service->carScorePenaltyFromPercent(25.0));
        $this->assertSame(12.0, $this->service->carScorePenaltyFromPercent(5.0));
    }

    public function test_ui_text_color_is_green_when_good(): void
    {
        $this->assertSame('#10b981', $this->service->uiTextColor(800, 1000));
    }

    public function test_ui_text_color_is_red_when_critical(): void
    {
        $this->assertSame('#ef4444', $this->service->uiTextColor(50, 1000));
        $this->assertSame('#ef4444', $this->service->uiTextColor(10, 100));
    }

    public function test_ui_text_color_smoothly_blends_yellow_toward_red_in_worn_band(): void
    {
        $justBelowGood = $this->service->uiTextColor(690, 1000);
        $midWorn = $this->service->uiTextColor(400, 1000);
        $nearCritical = $this->service->uiTextColor(150, 1000);

        $this->assertSame('#10b981', $this->service->uiTextColor(700, 1000));
        $this->assertNotSame($justBelowGood, $midWorn);
        $this->assertNotSame($midWorn, $nearCritical);
        $this->assertNotSame('#ef4444', $midWorn);
        $this->assertNotSame('#10b981', $midWorn);
    }
}
