<?php

namespace Tests\Feature;

use App\Models\StatisticType;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Village;
use App\Models\VillageStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseRoles();
    }

    #[Test]
    public function it_paginates_statistics_with_default_per_page(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        // Create 20 statistics
        VillageStatistic::factory()->count(20)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'created_by' => $officer->id,
        ]);

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics");

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(15, 'data');
    }

    #[Test]
    public function it_paginates_with_custom_per_page(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        VillageStatistic::factory()->count(25)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'created_by' => $officer->id,
        ]);

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?per_page=10");

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonCount(10, 'data');
    }

    #[Test]
    public function it_limits_maximum_per_page_to_100(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        VillageStatistic::factory()->count(150)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'created_by' => $officer->id,
        ]);

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?per_page=200");

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(100, 'data');
    }

    #[Test]
    public function it_handles_invalid_per_page_values(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        VillageStatistic::factory()->count(20)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'created_by' => $officer->id,
        ]);

        // Test negative value
        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?per_page=-10");
        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15); // Falls back to default

        // Test zero value
        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?per_page=0");
        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15); // Falls back to default
    }

    #[Test]
    public function it_navigates_to_specific_page(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        VillageStatistic::factory()->count(30)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'created_by' => $officer->id,
        ]);

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?per_page=10&page=2");

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 30)
            ->assertJsonCount(10, 'data');
    }

    #[Test]
    public function it_handles_page_beyond_last_page(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        VillageStatistic::factory()->count(20)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'created_by' => $officer->id,
        ]);

        // Request page 10 when only 2 pages exist
        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?per_page=10&page=10");

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 10)
            ->assertJsonCount(0, 'data'); // Empty result
    }

    #[Test]
    public function it_sorts_statistics_correctly(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        // Create statistics with different years
        VillageStatistic::factory()->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'indicator_name' => 'A Indicator',
            'year' => 2022,
            'created_by' => $officer->id,
        ]);

        VillageStatistic::factory()->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'indicator_name' => 'B Indicator',
            'year' => 2024,
            'created_by' => $officer->id,
        ]);

        VillageStatistic::factory()->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'indicator_name' => 'C Indicator',
            'year' => 2023,
            'created_by' => $officer->id,
        ]);

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics");

        $response->assertOk();

        $data = $response->json('data');

        // Should be sorted by year DESC, then indicator_name ASC
        $this->assertEquals(2024, $data[0]['year']);
        $this->assertEquals(2023, $data[1]['year']);
        $this->assertEquals(2022, $data[2]['year']);
    }

    #[Test]
    public function it_preserves_query_parameters_in_pagination_links(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        VillageStatistic::factory()->count(30)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'year' => 2024,
            'created_by' => $officer->id,
        ]);

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?year=2024&per_page=10");

        $response->assertOk();
        // Verify that pagination metadata is correct
        $this->assertEquals(30, $response->json('meta.total'));
    }

    #[Test]
    public function it_returns_empty_results_for_nonexistent_data(): void
    {
        $village = Village::factory()->create();

        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics");

        $response->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_paginates_with_filters_applied(): void
    {
        $village = Village::factory()->create();
        $type1 = StatisticType::factory()->create();
        $type2 = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        // Create 10 statistics for type1 in 2024
        VillageStatistic::factory()->count(10)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type1->id,
            'year' => 2024,
            'created_by' => $officer->id,
        ]);

        // Create 5 statistics for type2 in 2024
        VillageStatistic::factory()->count(5)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type2->id,
            'year' => 2024,
            'created_by' => $officer->id,
        ]);

        // Create 5 statistics for type1 in 2023
        VillageStatistic::factory()->count(5)->create([
            'village_id' => $village->id,
            'statistic_type_id' => $type1->id,
            'year' => 2023,
            'created_by' => $officer->id,
        ]);

        // Filter by year and type
        $response = $this->getJson("/api/v1/villages/{$village->id}/statistics?year=2024&statistic_type_id={$type1->id}");

        $response->assertOk()
            ->assertJsonPath('meta.total', 10)
            ->assertJsonCount(10, 'data');
    }

    protected function createVillageOfficer(Village $village): User
    {
        $role = UserRole::where('role_name', UserRole::VILLAGE_OFFICER)->first();

        return User::factory()
            ->for($role, 'role')
            ->for($village, 'village')
            ->create();
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
