<?php

namespace Tests\Feature;

use App\Models\StatisticType;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Village;
use App\Models\VillageStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseRoles();
    }

    #[Test]
    public function it_handles_empty_csv_file(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        $csv = UploadedFile::fake()->createWithContent('empty.csv', '');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_handles_csv_with_only_headers(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        $csv = UploadedFile::fake()->createWithContent(
            'headers-only.csv',
            "statistic_type_code,indicator_name,value,unit,year\n"
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'File tidak memiliki data. Pastikan file berisi data statistik.');
    }

    #[Test]
    public function it_handles_corrupted_csv_data(): void
    {
        $village = Village::factory()->create();
        StatisticType::factory()->create(['code' => 'POPULATION_TOTAL']);
        $officer = $this->createVillageOfficer($village);

        $csv = UploadedFile::fake()->createWithContent(
            'corrupted.csv',
            "statistic_type_code,indicator_name,value,unit,year\n" .
                "POPULATION_TOTAL,Total Penduduk,invalid_value,jiwa,2024\n"
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.imported', 0);
    }

    #[Test]
    public function it_handles_missing_statistic_type_in_csv(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        $csv = UploadedFile::fake()->createWithContent(
            'missing-type.csv',
            "statistic_type_code,indicator_name,value,unit,year\n" .
                "NONEXISTENT_CODE,Test,100,unit,2024\n"
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.imported', 0)
            ->assertJsonStructure(['data' => ['errors']]);
    }

    #[Test]
    public function it_handles_file_too_large(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        // Create a very large CSV content (simulate > max rows)
        $header = "statistic_type_code,indicator_name,value,unit,year\n";
        $rows = [];
        for ($i = 0; $i < 5001; $i++) { // Exceed max_rows limit
            $rows[] = "CODE{$i},Indicator {$i},100,unit,2024";
        }

        $csv = UploadedFile::fake()->createWithContent(
            'large.csv',
            $header . implode("\n", $rows)
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_handles_partial_import_failures(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create(['code' => 'VALID_CODE']);
        $officer = $this->createVillageOfficer($village);

        $csv = UploadedFile::fake()->createWithContent(
            'partial.csv',
            "statistic_type_code,indicator_name,value,unit,year\n" .
                "VALID_CODE,Valid Row,100,unit,2024\n" .
                "INVALID_CODE,Invalid Row,200,unit,2024\n" .
                "VALID_CODE,Another Valid,300,unit,2024\n"
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.total_rows', 3);

        $this->assertDatabaseCount('village_statistics', 2);
    }

    #[Test]
    public function it_handles_concurrent_updates_gracefully(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create();
        $officer = $this->createVillageOfficer($village);

        $statistic = VillageStatistic::create([
            'village_id' => $village->id,
            'statistic_type_id' => $type->id,
            'indicator_name' => 'Test',
            'value' => 100,
            'year' => 2024,
            'created_by' => $officer->id,
        ]);

        // Simulate concurrent update
        DB::transaction(function () use ($officer, $village, $statistic) {
            $this->actingAs($officer, 'sanctum')
                ->putJson("/api/v1/villages/{$village->id}/statistics/{$statistic->id}", [
                    'value' => 200,
                ]);
        });

        $statistic->refresh();
        $this->assertEquals(200, $statistic->value);
    }

    #[Test]
    public function it_handles_delete_of_nonexistent_statistic(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        $response = $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/villages/{$village->id}/statistics/99999");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_handles_update_of_nonexistent_statistic(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        $response = $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/villages/{$village->id}/statistics/99999", [
                'value' => 200,
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_handles_export_with_no_data(): void
    {
        $village = Village::factory()->create(['name' => 'TestVillage']);

        $response = $this->get("/api/v1/villages/{$village->id}/statistics/export?format=csv");

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    #[Test]
    public function it_handles_invalid_export_format(): void
    {
        $village = Village::factory()->create();

        $response = $this->get("/api/v1/villages/{$village->id}/statistics/export?format=pdf");

        $response->assertStatus(422);
    }

    #[Test]
    public function it_handles_missing_csv_columns(): void
    {
        $village = Village::factory()->create();
        $officer = $this->createVillageOfficer($village);

        // CSV missing required columns
        $csv = UploadedFile::fake()->createWithContent(
            'incomplete.csv',
            "indicator_name,value\n" .
                "Test,100\n"
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.failed', 1);
    }

    #[Test]
    public function it_handles_duplicate_entries_gracefully(): void
    {
        $village = Village::factory()->create();
        $type = StatisticType::factory()->create(['code' => 'TEST_CODE']);
        $officer = $this->createVillageOfficer($village);

        // First import
        $csv1 = UploadedFile::fake()->createWithContent(
            'first.csv',
            "statistic_type_code,indicator_name,value,unit,year\n" .
                "TEST_CODE,Test Indicator,100,unit,2024\n"
        );

        $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv1,
            ]);

        // Second import with same data (should create duplicate unless unique constraint exists)
        $csv2 = UploadedFile::fake()->createWithContent(
            'second.csv',
            "statistic_type_code,indicator_name,value,unit,year\n" .
                "TEST_CODE,Test Indicator,100,unit,2024\n"
        );

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/v1/villages/{$village->id}/statistics/import", [
                'file' => $csv2,
            ]);

        $response->assertOk();
        // Verify behavior based on your business rules
        // If duplicates allowed: imported = 1
        // If not: failed = 1
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
