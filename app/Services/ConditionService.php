<?php

namespace App\Services;

class ConditionService
{
    public function percent(int $current, int $max): float
    {
        if ($max <= 0) {
            return 100.0;
        }

        return max(0.0, min(100.0, ($current / $max) * 100));
    }

    /**
     * Multiplier applied to a part's stat bonuses (1.0 = full bonus).
     */
    public function partStatFactor(int $current, int $max): float
    {
        $percent = $this->percent($current, $max);

        if ($percent >= $this->goodThresholdPercent()) {
            return 1.0;
        }

        if ($percent >= 1.0) {
            $goodFloor = $this->goodThresholdPercent();

            return 0.5 + (0.5 * ($percent - 1.0) / ($goodFloor - 1.0));
        }

        if ($percent > 0.0) {
            return 0.5 * ($percent / 1.0);
        }

        return 0.0;
    }

    /**
     * Flat score penalty for car condition (cars are never destroyed).
     */
    public function carScorePenalty(int $current, int $max): float
    {
        $percent = $this->percent($current, $max);

        if ($percent >= $this->goodThresholdPercent()) {
            return 0.0;
        }

        if ($percent <= $this->criticalThresholdPercent()) {
            return (float) config('game.condition.penalties.critical', 12);
        }

        if ($percent >= $this->wornThresholdPercent()) {
            return (float) config('game.condition.penalties.worn', 5);
        }

        return (float) config('game.condition.penalties.damaged', 8);
    }

    public function carScorePenaltyFromPercent(float $conditionPercent): float
    {
        if ($conditionPercent >= $this->goodThresholdPercent()) {
            return 0.0;
        }

        if ($conditionPercent <= $this->criticalThresholdPercent()) {
            return (float) config('game.condition.penalties.critical', 12);
        }

        if ($conditionPercent >= $this->wornThresholdPercent()) {
            return (float) config('game.condition.penalties.worn', 5);
        }

        return (float) config('game.condition.penalties.damaged', 8);
    }

    /**
     * UI text color as hex — green when good, smooth yellow→orange→red when worn.
     */
    public function uiTextColor(int $current, int $max): string
    {
        $percent = $this->percent($current, $max);

        if ($percent >= $this->goodThresholdPercent()) {
            return (string) config('game.condition.ui.green', '#10b981');
        }

        if ($percent <= $this->criticalThresholdPercent()) {
            return (string) config('game.condition.ui.red', '#ef4444');
        }

        $yellow = $this->hexToRgb((string) config('game.condition.ui.yellow', '#facc15'));
        $red = $this->hexToRgb((string) config('game.condition.ui.red', '#ef4444'));

        // yellow × (condition%) blended toward red — orange in the middle, red at low %.
        $ratio = $percent / 100;

        return $this->lerpRgb($yellow, $red, 1 - $ratio);
    }

    /**
     * @param  array{r: int, g: int, b: int}  $from
     * @param  array{r: int, g: int, b: int}  $to
     */
    private function lerpRgb(array $from, array $to, float $t): string
    {
        $t = max(0.0, min(1.0, $t));

        $r = (int) round($from['r'] + ($to['r'] - $from['r']) * $t);
        $g = (int) round($from['g'] + ($to['g'] - $from['g']) * $t);
        $b = (int) round($from['b'] + ($to['b'] - $from['b']) * $t);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * @return array{r: int, g: int, b: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    public function goodThresholdPercent(): float
    {
        return (float) config('game.condition.tiers.good_min_percent', 70);
    }

    public function wornThresholdPercent(): float
    {
        return (float) config('game.condition.tiers.worn_min_percent', 40);
    }

    public function criticalThresholdPercent(): float
    {
        return (float) config('game.condition.tiers.critical_max_percent', 10);
    }
}
