<?php

namespace App\View\Components;

use App\Services\ConditionService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ConditionMeter extends Component
{
    public float $percent;

    public string $textColor;

    public function __construct(
        public int $current,
        public int $max,
        ?ConditionService $conditionService = null,
    ) {
        $service = $conditionService ?? app(ConditionService::class);
        $this->percent = $service->percent($current, $max);
        $this->textColor = $service->uiTextColor($current, $max);
    }

    public function render(): View|Closure|string
    {
        return view('components.condition-meter');
    }
}
