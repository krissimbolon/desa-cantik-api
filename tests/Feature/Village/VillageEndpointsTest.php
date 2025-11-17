<?php

namespace Tests\Feature\Village;

use App\Models\Village;
use App\Models\VillageProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageEndpointsTest extends TestCase
{
    public function test_villages_index_returns_frontend_ready_payload(): void
    {
        $village = Village::factory()->create([
            'name' => 'Test Village',
            'kecamatan' => 'Test District',
            'kabupaten' => 'Test Regency',
            'provinsi' => 'Test Province',
            'is_visible' => true,
        ]);

        VillageProfile::factory()
            ->for($village)
            ->state([
                'population' => 1500,
                'households' => 300,
                'male_population' => 750,
                'female_population' => 750,
                'area' => 12.5,
                'thumbnail_url' => 'https://example.com/thumb.jpg',
            ])
            ->create();

        $response = $this->getJson('/api/v1/villages');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', (string) $village->id);
        $response->assertJsonPath('data.0.name', 'Test Village');
        $response->assertJsonPath('data.0.district', 'Test District');
        $response->assertJsonPath('data.0.regency', 'Test Regency');
        $response->assertJsonPath('data.0.province', 'Test Province');
        $response->assertJsonPath('data.0.population', 1500);
        $response->assertJsonPath('data.0.status', 'Aktif');
        $response->assertJsonPath('data.0.image', 'https://example.com/thumb.jpg');
        $response->assertJsonPath('data.0.area', 12.5);
        $response->assertJsonPath('data.0.households', 300);
        $response->assertJsonPath('data.0.malePopulation', 750);
        $response->assertJsonPath('data.0.femalePopulation', 750);
    }

    public function test_village_detail_returns_formatted_payload(): void
    {
        $village = Village::factory()->create([
            'is_visible' => false,
            'logo_url' => null,
        ]);

        VillageProfile::factory()
            ->for($village)
            ->state([
                'population' => 2000,
                'households' => 0,
                'male_population' => 0,
                'female_population' => 0,
                'area' => 1.0,
                'thumbnail_url' => null,
                'logo_url' => null,
                'foto_url' => null,
            ])
            ->create();

        $response = $this->getJson("/api/v1/villages/{$village->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $village->id);
        $response->assertJsonPath('data.status', 'Tidak Aktif');
        $response->assertJsonPath('data.image', 'https://placehold.co/800x600/1C6EA4/FFFFFF?text=Desa+Cantik');
        $response->assertJsonPath('data.malePopulation', 0);
        $response->assertJsonPath('data.femalePopulation', 0);
    }

    public function test_village_detail_returns_404_for_missing_record(): void
    {
        $this->getJson('/api/v1/villages/999')->assertNotFound();
    }
}
