<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ThematicMap; // Pastikan model ThematicMap digunakan
use App\Models\MapPoint;    // Pastikan model MapPoint ada

class MapPointsController extends Controller
{
    // POST /thematic-maps/{id}/points (Create Titik Peta)
    public function createMapPoint(Request $request, $id)
    {
        $thematicMap = ThematicMap::find($id);
        if (! $thematicMap) {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'icon_url' => 'nullable|url|max:500',
            'metadata' => 'nullable|array',
            // Compatibility: allow coordinates array [lat, lng]
            'coordinates' => 'nullable|array|size:2',
        ]);

        $payload = $this->mergeCoordinates($validated);

        $mapPoint = $thematicMap->mapPoints()->create($payload);
        return response()->json([
            'success' => true,
            'data' => $mapPoint,
        ], 201); // Kembalikan data titik peta yang baru
    }

    // PUT /thematic-maps/{id}/points/{pointId} (Update Titik)
    public function updateMapPoint(Request $request, $id, $pointId)
    {
        $thematicMap = ThematicMap::find($id);
        if (! $thematicMap) {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }

        $mapPoint = $thematicMap->mapPoints()->find($pointId);
        if (! $mapPoint) {
            return response()->json(['message' => 'Map point not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'icon_url' => 'nullable|url|max:500',
            'metadata' => 'nullable|array',
            'coordinates' => 'nullable|array|size:2',
        ]);

        $payload = $this->mergeCoordinates($validated);

        $mapPoint->update($payload); // Update titik peta
        return response()->json([
            'success' => true,
            'data' => $mapPoint,
        ]);
    }

    // DELETE /thematic-maps/{id}/points/{pointId} (Delete Titik)
    public function deleteMapPoint($id, $pointId)
    {
        $thematicMap = ThematicMap::find($id);
        if (! $thematicMap) {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }

        $mapPoint = $thematicMap->mapPoints()->find($pointId);
        if (! $mapPoint) {
            return response()->json(['message' => 'Map point not found'], 404);
        }

        $mapPoint->delete(); // Menghapus titik peta
        return response()->json([
            'success' => true,
            'message' => 'Map point deleted',
        ]);
    }

    // POST /thematic-maps/{id}/points/{pointId}/image (Upload Gambar Titik)
    public function uploadMapPointImage(Request $request, $id, $pointId)
    {
        $thematicMap = ThematicMap::find($id);
        if ($thematicMap) {
            $mapPoint = $thematicMap->mapPoints()->find($pointId);
            if ($mapPoint) {
                // Validasi file gambar
                $request->validate([
                    'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                ]);

                // Simpan gambar
                $imagePath = $request->file('image')->store('public/map_points_images');
                $mapPoint->icon_url = $imagePath; // Simpan path gambar ke titik peta
                $mapPoint->save();

                return response()->json(['success' => true, 'message' => 'Image uploaded successfully', 'image' => $imagePath]);
            } else {
                return response()->json(['message' => 'Map point not found'], 404);
            }
        } else {
            return response()->json(['message' => 'Thematic map not found'], 404);
        }
    }

    private function mergeCoordinates(array $validated): array
    {
        if (isset($validated['coordinates'])) {
            $validated['latitude'] = $validated['latitude'] ?? ($validated['coordinates'][0] ?? null);
            $validated['longitude'] = $validated['longitude'] ?? ($validated['coordinates'][1] ?? null);
            unset($validated['coordinates']);
        }

        return $validated;
    }
}
