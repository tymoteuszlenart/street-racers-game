<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_factory_sets_is_admin_without_mass_assignment(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->refresh()->is_admin);
    }
}
