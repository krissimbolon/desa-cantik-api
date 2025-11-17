<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThematicMap;
use App\Models\Village;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class ThematicMapController extends Controller
{
    /**
     * Get all thematic maps for a village (Public)
     */
    public function index($villageId): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        $maps = ThematicMap::where('desa_id', $village->id)
            ->with('mapPoints:id,thematic_map_id,name,latitude,longitude,category')
            ->get()
            ->map(function ($map) {
                return [
                    'id' => $map->id,
                    'village_id' => $map->desa_id,
                    'theme_name' => $map->map_name,
                    'description' => $map->description,
                    'icon' => $map->map_type,
                    'points_count' => $map->mapPoints->count(),
                    'created_at' => $map->created_at,
                    'updated_at' => $map->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $maps
        ]);
    }

    /**
     * Get thematic map detail with points (Public)
     */
    public function show($mapId): JsonResponse
    {
        $map = ThematicMap::with(['mapPoints', 'village:id,name,code'])->findOrFail($mapId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $map->id,
                'village' => [
                    'id' => $map->village->id,
                    'name' => $map->village->name,
                    'code' => $map->village->code,
                ],
                'theme_name' => $map->map_name,
                'description' => $map->description,
                'icon' => $map->map_type,
                'points' => $map->mapPoints->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'name' => $point->name,
                        'description' => $point->description,
                        'category' => $point->category,
                        'latitude' => $point->latitude,
                        'longitude' => $point->longitude,
                        'image_url' => $point->icon_url,
                        'additional_info' => $point->metadata,
                    ];
                }),
                'created_at' => $map->created_at,
                'updated_at' => $map->updated_at,
            ]
        ]);
    }

    /**
     * Create thematic map (Auth required)
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
                'message' => 'You do not have permission to create thematic map for this village'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'theme_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $map = ThematicMap::create([
            'desa_id' => $village->id,
            'map_name' => $request->theme_name,
            'description' => $request->description,
            'map_type' => $request->icon ?? 'default',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        ActivityLogger::log('create', $map, 'Thematic map created');

        return response()->json([
            'success' => true,
            'message' => 'Thematic map created successfully',
            'data' => [
                'id' => $map->id,
                'village_id' => $map->desa_id,
                'theme_name' => $map->map_name,
                'description' => $map->description,
                'icon' => $map->map_type,
            ]
        ], 201);
    }

    /**
     * Update thematic map (Auth required)
     */
    public function update(Request $request, $villageId, $mapId): JsonResponse
    {
        $village = Village::findOrFail($villageId);
        $map = ThematicMap::where('desa_id', $village->id)->findOrFail($mapId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this thematic map'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'theme_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $map->toArray();

        if ($request->has('theme_name')) $map->map_name = $request->theme_name;
        if ($request->has('description')) $map->description = $request->description;
        if ($request->has('icon')) $map->map_type = $request->icon;

        $map->save();

        ActivityLogger::log('update', $map, 'Thematic map updated', [
            'old_data' => $oldData,
            'new_data' => $map->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thematic map updated successfully',
            'data' => [
                'id' => $map->id,
                'village_id' => $map->desa_id,
                'theme_name' => $map->map_name,
                'description' => $map->description,
                'icon' => $map->map_type,
            ]
        ]);
    }

    /**
     * Delete thematic map (Auth required)
     */
    public function destroy(Request $request, $villageId, $mapId): JsonResponse
    {
        $village = Village::findOrFail($villageId);
        $map = ThematicMap::where('desa_id', $village->id)->findOrFail($mapId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this thematic map'
            ], 403);
        }

        ActivityLogger::log('delete', $map, 'Thematic map deleted', [
            'old_data' => $map->toArray(),
        ]);

        // Cascade delete all map points
        $map->mapPoints()->delete();
        $map->delete();

        return response()->json([
            'success' => true,
            'message' => 'Thematic map deleted successfully'
        ]);
    }
}
