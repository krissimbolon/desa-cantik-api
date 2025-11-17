<?php

namespace Tests\Feature\Dashboard;

use App\Models\ActivityLog;
use App\Models\Publication;
use App\Models\StatisticType;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Village;
use App\Models\VillageProfile;
use App\Models\VillageStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseRoles();
    }

    public function test_admin_dashboard_returns_summary(): void
    {
        $adminRole = UserRole::where('role_name', UserRole::BPS_ADMIN)->first();
        $admin = User::factory()
            ->for($adminRole, 'role')
            ->withoutVillage()
            ->create();

        $village = Village::factory()->create(['name' => 'Desa Makmur']);
        $profile = VillageProfile::factory()->for($village, 'village')->create(['is_featured' => true]);
        $statType = StatisticType::factory()->create(['code' => 'POPULATION_TOTAL', 'category' => 'kependudukan']);

        VillageStatistic::factory()->for($village, 'village')->for($statType)->create([
            'indicator_name' => 'Total Penduduk',
            'year' => now()->year,
            'created_by' => $admin->id,
        ]);

        Publication::factory()->for($village, 'village')->create([
            'published_at' => now(),
        ]);

        ActivityLog::factory()->for($admin)->for($village, 'village')->create([
            'action' => 'create',
            'model_type' => VillageStatistic::class,
            'description' => 'Menambahkan data statistik Total Penduduk',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/dashboard/admin');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary' => [
                        'total_villages',
                        'active_villages',
                        'inactive_villages',
                        'total_users',
                        'active_users',
                        'total_statistics',
                        'total_publications',
                        'total_thematic_maps',
                    ],
                    'recent_activities',
                    'villages_statistics',
                    'monthly_activities',
                ],
            ])
            ->assertJsonPath('data.recent_activities.0.action', 'create');
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $officerRole = UserRole::where('role_name', UserRole::VILLAGE_OFFICER)->first();
        $officer = User::factory()->for($officerRole, 'role')->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/dashboard/admin')
            ->assertStatus(403);
    }

    public function test_village_dashboard_returns_data_for_officer(): void
    {
        $officerRole = UserRole::where('role_name', UserRole::VILLAGE_OFFICER)->first();
        $officer = User::factory()->for($officerRole, 'role')->create();
        $village = $officer->village;

        VillageProfile::factory()->for($village, 'village')->create([
            'address' => 'Jl. Raya',
            'phone' => '0812',
            'email' => 'desa@example.com',
            'website' => 'https://desa.id',
            'logo_url' => 'https://example.com/logo.png',
        ]);

        $statType = StatisticType::factory()->create(['category' => 'ekonomi']);
        VillageStatistic::factory()->for($village, 'village')->for($statType)->create([
            'year' => now()->year,
            'indicator_name' => 'Jumlah UMKM',
        ]);

        ActivityLog::factory()->for($officer)->for($village, 'village')->create([
            'action' => 'create',
            'model_type' => VillageStatistic::class,
        ]);

        $response = $this->actingAs($officer, 'sanctum')->getJson('/api/v1/dashboard/village');

        $response->assertOk()
            ->assertJsonPath('data.village.id', $village->id)
            ->assertJsonPath('data.summary.total_statistics', 1)
            ->assertJsonPath('data.profile_completeness.percentage', 100);
    }

    public function test_public_dashboard_is_accessible(): void
    {
        $village = Village::factory()->create();
        $profile = VillageProfile::factory()->for($village, 'village')->create(['is_featured' => true]);

        Publication::factory()->for($village, 'village')->create([
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/dashboard/public');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary' => [
                        'total_villages',
                        'total_statistics',
                        'total_publications',
                    ],
                    'featured_villages',
                    'latest_publications',
                    'statistics_overview',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard/admin')
            ->assertStatus(401);
    }

    public function test_admin_can_view_specific_village_dashboard(): void
    {
        $adminRole = UserRole::where('role_name', UserRole::BPS_ADMIN)->first();
        $admin = User::factory()
            ->for($adminRole, 'role')
            ->withoutVillage()
            ->create();

        $village = Village::factory()->create();
        VillageProfile::factory()->for($village, 'village')->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard/village?village_id=' . $village->id);

        $response->assertOk()
            ->assertJsonPath('data.village.id', $village->id);
    }

    public function test_admin_cannot_view_village_dashboard_with_invalid_village_id(): void
    {
        $adminRole = UserRole::where('role_name', UserRole::BPS_ADMIN)->first();
        $admin = User::factory()
            ->for($adminRole, 'role')
            ->withoutVillage()
            ->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard/village?village_id=99999')
            ->assertStatus(422);
    }

    public function test_village_officer_cannot_view_other_village_dashboard(): void
    {
        $officerRole = UserRole::where('role_name', UserRole::VILLAGE_OFFICER)->first();
        $officer = User::factory()->for($officerRole, 'role')->create();

        $otherVillage = Village::factory()->create();

        // Officer tries to access other village - should still get their own village
        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/dashboard/village?village_id=' . $otherVillage->id);

        $response->assertOk()
            ->assertJsonPath('data.village.id', $officer->village_id);
    }

    public function test_dashboard_returns_proper_error_for_missing_village_id(): void
    {
        $adminRole = UserRole::where('role_name', UserRole::BPS_ADMIN)->first();
        $admin = User::factory()
            ->for($adminRole, 'role')
            ->withoutVillage()
            ->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard/village')
            ->assertStatus(422)
            ->assertJsonPath('error', 'INVALID_DASHBOARD_REQUEST');
    }

    protected function seedBaseRoles(): void
    {
        UserRole::updateOrCreate(
            ['role_name' => UserRole::BPS_ADMIN],
            ['display_name' => 'BPS Admin']
        );

        UserRole::updateOrCreate(
            ['role_name' => UserRole::VILLAGE_OFFICER],
            ['display_name' => 'Perangkat Desa']
        );

        UserRole::updateOrCreate(
            ['role_name' => UserRole::GUEST],
            ['display_name' => 'Masyarakat Umum']
        );
    }
}
