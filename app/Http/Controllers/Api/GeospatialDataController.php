<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeospatialData;
use App\Models\Village;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class GeospatialDataController extends Controller
{
    /**
     * Get all geospatial data for a village (Public)
     */
    public function index($villageId): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        $geospatialData = GeospatialData::where('desa_id', $village->id)
            ->get()
            ->map(function ($data) {
                return [
                    'id' => $data->id,
                    'village_id' => $data->desa_id,
                    'name' => $data->description,
                    'type' => $data->geometry_type,
                    'geometry' => $data->geojson_data,
                    'properties' => $data->properties ?? [],
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $geospatialData
        ]);
    }

    /**
     * Get single geospatial data (Public)
     */
    public function show($villageId, $geoId): JsonResponse
    {
        $data = GeospatialData::where('desa_id', $villageId)
            ->findOrFail($geoId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $data->id,
                'village_id' => $data->desa_id,
                'name' => $data->description,
                'type' => $data->geometry_type,
                'geometry' => $data->geojson_data,
                'properties' => $data->properties ?? [],
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ]
        ]);
    }

    /**
     * Create geospatial data (Auth required)
     */
    public function store(Request $request, $villageId): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add geospatial data for this village'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'geometry' => 'required|array',
            'properties' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = GeospatialData::create([
            'desa_id' => $village->id,
            'geometry_type' => $request->type,
            'geojson_data' => $request->geometry,
            'properties' => $request->properties ?? [],
            'description' => $request->name,
            'uploaded_by' => $user->id,
        ]);

        ActivityLogger::log('create', $data, 'Geospatial data created');

        return response()->json([
            'success' => true,
            'message' => 'Geospatial data created successfully',
            'data' => [
                'id' => $data->id,
                'village_id' => $data->desa_id,
                'name' => $data->description,
                'type' => $data->geometry_type,
                'geometry' => $data->geojson_data,
                'properties' => $data->properties ?? [],
            ]
        ], 201);
    }

    /**
     * Update geospatial data (Auth required)
     */
    public function update(Request $request, $villageId, $geoId): JsonResponse
    {
        $village = Village::findOrFail($villageId);
        $data = GeospatialData::where('desa_id', $village->id)->findOrFail($geoId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this geospatial data'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:100',
            'geometry' => 'sometimes|array',
            'properties' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $data->toArray();

        if ($request->has('name')) $data->description = $request->name;
        if ($request->has('type')) $data->geometry_type = $request->type;
        if ($request->has('geometry')) $data->geojson_data = $request->geometry;
        if ($request->has('properties')) $data->properties = $request->properties;

        $data->save();

        ActivityLogger::log('update', $data, 'Geospatial data updated', [
            'old_data' => $oldData,
            'new_data' => $data->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Geospatial data updated successfully',
            'data' => [
                'id' => $data->id,
                'village_id' => $data->desa_id,
                'name' => $data->description,
                'type' => $data->geometry_type,
                'geometry' => $data->geojson_data,
                'properties' => $data->properties ?? [],
            ]
        ]);
    }

    /**
     * Delete geospatial data (Auth required)
     */
    public function destroy(Request $request, $villageId, $geoId): JsonResponse
    {
        $village = Village::findOrFail($villageId);
        $data = GeospatialData::where('desa_id', $village->id)->findOrFail($geoId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this geospatial data'
            ], 403);
        }

        ActivityLogger::log('delete', $data, 'Geospatial data deleted', [
            'old_data' => $data->toArray(),
        ]);

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Geospatial data deleted successfully'
        ]);
    }
}
