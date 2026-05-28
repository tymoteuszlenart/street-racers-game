<?php

namespace Tests\Unit;

use App\Models\Club;
use App\Services\ClubPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClubPointServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubPointService $clubPointService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubPointService = app(ClubPointService::class);
    }

    public function test_adds_points_atomically(): void
    {
        $club = Club::factory()->create(['points' => 50]);

        $this->clubPointService->addPoints($club, 100);

        $this->assertSame(150, $club->fresh()->points);
    }

    public function test_rejects_non_positive_points(): void
    {
        $club = Club::factory()->create(['points' => 10]);

        $this->expectException(ValidationException::class);

        $this->clubPointService->addPoints($club, 0);
    }
}
