<?php

namespace Tests\Unit;

use App\Enums\RaceType;
use App\Services\RaceScoreCalculator;
use Tests\TestCase;

class RaceScoreCalculatorTest extends TestCase
{
    private RaceScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(RaceScoreCalculator::class);
    }

    public function test_higher_driver_stats_increase_race_bonus(): void
    {
        $stats = [
            'power' => 50,
            'acceleration' => 50,
            'grip' => 50,
            'handling' => 50,
            'condition_percent' => 100,
        ];

        $low = $this->calculator->calculate($stats, [
            'power' => 1,
            'acceleration' => 1,
            'grip' => 1,
            'handling' => 1,
        ], 0.0);

        $high = $this->calculator->calculate($stats, [
            'power' => 10,
            'acceleration' => 10,
            'grip' => 10,
            'handling' => 10,
        ], 0.0);

        $this->assertGreaterThan($low['score'], $high['score']);
        $this->assertGreaterThan($low['breakdown']['driver_bonus'], $high['breakdown']['driver_bonus']);
    }

    public function test_drag_race_favors_force_over_control_for_same_point_budget(): void
    {
        $stats = [
            'power' => 50,
            'acceleration' => 50,
            'grip' => 50,
            'handling' => 50,
            'condition_percent' => 100,
        ];

        $forceBuild = [
            'power' => 13,
            'acceleration' => 1,
            'grip' => 1,
            'handling' => 1,
        ];

        $controlBuild = [
            'power' => 1,
            'acceleration' => 1,
            'grip' => 13,
            'handling' => 1,
        ];

        $forceBonus = $this->calculator->driverRaceBonus($forceBuild, RaceType::Drag);
        $controlBonus = $this->calculator->driverRaceBonus($controlBuild, RaceType::Drag);

        $this->assertGreaterThan($controlBonus, $forceBonus);
    }

    public function test_drift_race_favors_control_over_force_for_same_point_budget(): void
    {
        $forceBuild = [
            'power' => 13,
            'acceleration' => 1,
            'grip' => 1,
            'handling' => 1,
        ];

        $controlBuild = [
            'power' => 1,
            'acceleration' => 1,
            'grip' => 13,
            'handling' => 1,
        ];

        $forceBonus = $this->calculator->driverRaceBonus($forceBuild, RaceType::Drift);
        $controlBonus = $this->calculator->driverRaceBonus($controlBuild, RaceType::Drift);

        $this->assertGreaterThan($forceBonus, $controlBonus);
    }

    public function test_specialized_build_beats_wrong_specialization_in_matching_race_type(): void
    {
        $dragSpecialist = [
            'power' => 10,
            'acceleration' => 10,
            'grip' => 1,
            'handling' => 1,
        ];

        $driftSpecialist = [
            'power' => 1,
            'acceleration' => 1,
            'grip' => 10,
            'handling' => 10,
        ];

        $dragSpecialistOnDrag = $this->calculator->driverRaceBonus($dragSpecialist, RaceType::Drag);
        $driftSpecialistOnDrag = $this->calculator->driverRaceBonus($driftSpecialist, RaceType::Drag);

        $this->assertGreaterThan($driftSpecialistOnDrag, $dragSpecialistOnDrag);
    }

    public function test_breakdown_includes_race_type_and_affinity(): void
    {
        $outcome = $this->calculator->calculate(
            [
                'power' => 50,
                'acceleration' => 50,
                'grip' => 50,
                'handling' => 50,
                'condition_percent' => 100,
            ],
            ['power' => 5, 'acceleration' => 5, 'grip' => 5, 'handling' => 5],
            0.0,
            RaceType::Sprint,
        );

        $this->assertSame('sprint', $outcome['breakdown']['race_type']);
        $this->assertSame(
            $this->calculator->affinityWeights(RaceType::Sprint),
            $outcome['breakdown']['driver_affinity'],
        );
    }
}
