<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Village; // Pastikan untuk menggunakan model Village jika diperlukan
use App\Models\ThematicMap; // Pastikan model thematic map ada

class ThematicMapsController extends Controller
{
    // GET /villages/{id}/thematic-maps (Get Tema Peta)
    public function getThematicMaps($id)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $maps = $village->thematicMaps()
            ->withCount('mapPoints')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($map) => $this->formatThematicMap($map));

        return response()->json([
            'success' => true,
            'data' => $maps,
        ]);
    }

    // GET /thematic-maps/{id} (Detail Tema & Points)
    public function getThematicMapDetail($id)
    {
        $thematicMap = ThematicMap::with('mapPoints')->find($id);
        if (! $thematicMap) {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatThematicMap($thematicMap, includePoints: true),
        ]);
    }

    // POST /villages/{id}/thematic-maps (Create Tema)
    public function createThematicMap(Request $request, $id)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $validated = $request->validate([
            'map_name' => 'required|string|max:255',
            'map_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'layer_config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $thematicMap = $village->thematicMaps()->create([
            'map_name' => $validated['map_name'],
            'map_type' => $validated['map_type'],
            'description' => $validated['description'] ?? null,
            'layer_config' => $validated['layer_config'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]); // Menyimpan peta tematik untuk desa

        return response()->json([
            'success' => true,
            'message' => 'Thematic map created',
            'data' => $this->formatThematicMap($thematicMap),
        ], 201);
    }

    // PUT /villages/{id}/thematic-maps/{id} (Update Tema)
    public function updateThematicMap(Request $request, $id, $mapId)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $thematicMap = $village->thematicMaps()->find($mapId);
        if (! $thematicMap) {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }

        $validated = $request->validate([
            'map_name' => 'sometimes|required|string|max:255',
            'map_type' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'layer_config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $thematicMap->update($validated); // Update data peta tematik

        return response()->json([
            'success' => true,
            'data' => $this->formatThematicMap($thematicMap),
        ]);
    }

    // DELETE /villages/{id}/thematic-maps/{id} (Delete Tema)
    public function deleteThematicMap($id, $mapId)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $thematicMap = $village->thematicMaps()->find($mapId);
        if (! $thematicMap) {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }

        $thematicMap->delete(); // Menghapus peta tematik

        return response()->json([
            'success' => true,
            'message' => 'Thematic map deleted',
        ]);
    }

    private function formatThematicMap(ThematicMap $map, bool $includePoints = false): array
    {
        $layerConfig = $map->layer_config ?? [];
        $color = is_array($layerConfig) && isset($layerConfig['color'])
            ? $layerConfig['color']
            : '#1C6EA4';

        $payload = [
            'id' => $map->id,
            'name' => $map->map_name,
            'map_type' => $map->map_type,
            'description' => $map->description,
            'geoId' => $layerConfig['geoId'] ?? null, // compatibility with FE mock
            'color' => $color,
            'layer_config' => $layerConfig,
            'is_active' => $map->is_active,
            'map_points_count' => $map->map_points_count ?? ($map->mapPoints()->count()),
            'created_at' => $map->created_at,
            'updated_at' => $map->updated_at,
        ];

        if ($includePoints) {
            $payload['map_points'] = $map->mapPoints->map(function ($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'description' => $point->description,
                    'category' => $point->category,
                    'latitude' => $point->latitude,
                    'longitude' => $point->longitude,
                    'icon_url' => $point->icon_url,
                    'metadata' => $point->metadata,
                ];
            });
        }

        return $payload;
    }
}
