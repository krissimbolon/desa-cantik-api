<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Village; 

class GeospatialDataController extends Controller
{
    // GET /villages/{id}/geospatial (Get Data GeoJSON)
    public function getGeoSpatialData($id)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $data = $village->geospatialData()
            ->orderByDesc('created_at')
            ->get(['id', 'geometry_type', 'geojson_data', 'description', 'uploaded_by', 'created_at', 'updated_at'])
            ->map(fn($row) => $this->formatGeoResource($village->id, $row));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // GET /villages/{id}/geospatial/{geoId} (single resource for GeoJSON fetch)
    public function showGeoSpatialData($id, $geoId)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $geospatialData = $village->geospatialData()
            ->where('id', $geoId)
            ->first();

        if (! $geospatialData) {
            return response()->json(['message' => 'Geospatial data not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatGeoResource($village->id, $geospatialData, includeGeoJson: true),
        ]);
    }

    // POST /villages/{id}/geospatial (Create Geospatial Data)
    public function createGeoSpatialData(Request $request, $id)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $validated = $request->validate([
            'geometry_type' => 'required|string|max:50',
            'geojson_data' => 'required', // Bisa string JSON atau array
            'description' => 'nullable|string',
        ]);

        $geojsonData = $validated['geojson_data'];
        if (is_string($geojsonData)) {
            $decoded = json_decode($geojsonData, true);
            $geojsonData = $decoded ?? $geojsonData;
        }
        
        $geospatial = $village->geospatialData()->create([
            'geometry_type' => $validated['geometry_type'],
            'geojson_data' => $geojsonData,
            'description' => $validated['description'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Geospatial data created successfully',
            'data' => $this->formatGeoResource($village->id, $geospatial),
        ], 201);
    }

    // PUT /villages/{id}/geospatial/{geoId} (Update Geospatial Data)
    public function updateGeoSpatialData(Request $request, $id, $geoId)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $geospatialData = $village->geospatialData()->find($geoId);
        if (! $geospatialData) {
            return response()->json(['message' => 'Geospatial data not found'], 404);
        }

        $validated = $request->validate([
            'geometry_type' => 'sometimes|required|string|max:50',
            'geojson_data' => 'sometimes|required', // Bisa string JSON atau array
            'description' => 'nullable|string',
        ]);

        if (array_key_exists('geojson_data', $validated) && is_string($validated['geojson_data'])) {
            $decoded = json_decode($validated['geojson_data'], true);
            $validated['geojson_data'] = $decoded ?? $validated['geojson_data'];
        }

        $geospatialData->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatGeoResource($village->id, $geospatialData),
        ]);
    }

    // DELETE /villages/{id}/geospatial/{geoId} (Delete Geospatial Data)
    public function deleteGeoSpatialData($id, $geoId)
    {
        $village = Village::find($id);
        if (! $village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        $geospatialData = $village->geospatialData()->find($geoId);
        if (! $geospatialData) {
            return response()->json(['message' => 'Geospatial data not found'], 404);
        }

        $geospatialData->delete(); // Menghapus data geospasial

        return response()->json([
            'success' => true,
            'message' => 'Geospatial data deleted',
        ]);
    }

    private function formatGeoResource(int $villageId, $row, bool $includeGeoJson = false): array
    {
        $payload = [
            'id' => $row->id,
            'name' => $row->description ?? sprintf('Geospatial Layer %d', $row->id),
            'type' => $row->geometry_type,
            'source' => url(sprintf('/api/villages/%d/geospatial/%d', $villageId, $row->id)),
            'uploaded_by' => $row->uploaded_by,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];

        if ($includeGeoJson) {
            $payload['geojson_data'] = $row->geojson_data;
        }

        return $payload;
    }
}
