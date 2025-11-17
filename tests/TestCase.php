<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure base roles exist for every test case
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function actingAsAdmin(): \App\Models\User
    {
        $adminRole = \App\Models\UserRole::firstWhere('role_name', \App\Models\UserRole::BPS_ADMIN)
            ?? \App\Models\UserRole::factory()->bpsAdmin()->create();

        return \App\Models\User::factory()->create([
            'role_id' => $adminRole->id,
            'village_id' => null,
        ]);
    }

    protected function actingAsVillageOfficer(?\App\Models\Village $village = null): \App\Models\User
    {
        $village ??= \App\Models\Village::factory()->create();
        $role = \App\Models\UserRole::firstWhere('role_name', \App\Models\UserRole::VILLAGE_OFFICER)
            ?? \App\Models\UserRole::factory()->villageOfficer()->create();

        return \App\Models\User::factory()->create([
            'role_id' => $role->id,
            'village_id' => $village->id,
        ]);
    }
}
