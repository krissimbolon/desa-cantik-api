<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapPoint;
use App\Models\ThematicMap;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class MapPointController extends Controller
{
    /**
     * Create map point (Auth required)
     */
    public function store(Request $request, $mapId): JsonResponse
    {
        $map = ThematicMap::with('village')->findOrFail($mapId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $map->desa_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add points to this thematic map'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'image_url' => 'nullable|url',
            'additional_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $point = MapPoint::create([
            'thematic_map_id' => $map->id,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'icon_url' => $request->image_url,
            'metadata' => $request->additional_info ?? [],
        ]);

        ActivityLogger::log('create', $point, 'Map point created', [
            'village_id' => $map->desa_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Map point created successfully',
            'data' => [
                'id' => $point->id,
                'thematic_map_id' => $point->thematic_map_id,
                'name' => $point->name,
                'description' => $point->description,
                'category' => $point->category,
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'image_url' => $point->icon_url,
                'additional_info' => $point->metadata,
            ]
        ], 201);
    }

    /**
     * Update map point (Auth required)
     */
    public function update(Request $request, $mapId, $pointId): JsonResponse
    {
        $map = ThematicMap::findOrFail($mapId);
        $point = MapPoint::where('thematic_map_id', $map->id)->findOrFail($pointId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $map->desa_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this map point'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'image_url' => 'nullable|url',
            'additional_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $point->toArray();

        if ($request->has('name')) $point->name = $request->name;
        if ($request->has('description')) $point->description = $request->description;
        if ($request->has('category')) $point->category = $request->category;
        if ($request->has('latitude')) $point->latitude = $request->latitude;
        if ($request->has('longitude')) $point->longitude = $request->longitude;
        if ($request->has('image_url')) $point->icon_url = $request->image_url;
        if ($request->has('additional_info')) $point->metadata = $request->additional_info;

        $point->save();

        ActivityLogger::log('update', $point, 'Map point updated', [
            'old_data' => $oldData,
            'new_data' => $point->toArray(),
            'village_id' => $map->desa_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Map point updated successfully',
            'data' => [
                'id' => $point->id,
                'thematic_map_id' => $point->thematic_map_id,
                'name' => $point->name,
                'description' => $point->description,
                'category' => $point->category,
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'image_url' => $point->icon_url,
                'additional_info' => $point->metadata,
            ]
        ]);
    }

    /**
     * Delete map point (Auth required)
     */
    public function destroy(Request $request, $mapId, $pointId): JsonResponse
    {
        $map = ThematicMap::findOrFail($mapId);
        $point = MapPoint::where('thematic_map_id', $map->id)->findOrFail($pointId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $map->desa_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this map point'
            ], 403);
        }

        ActivityLogger::log('delete', $point, 'Map point deleted', [
            'old_data' => $point->toArray(),
            'village_id' => $map->desa_id,
        ]);

        $point->delete();

        return response()->json([
            'success' => true,
            'message' => 'Map point deleted successfully'
        ]);
    }

    /**
     * Upload map point image (Auth required)
     */
    public function uploadImage(Request $request, $mapId, $pointId): JsonResponse
    {
        $map = ThematicMap::findOrFail($mapId);
        $point = MapPoint::where('thematic_map_id', $map->id)->findOrFail($pointId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $map->desa_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this map point'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,jpg,png|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Delete old image if exists
        if ($point->icon_url && Storage::exists($point->icon_url)) {
            Storage::delete($point->icon_url);
        }

        // Store new image
        $path = $request->file('image')->store('map-points', 'public');
        $point->icon_url = Storage::url($path);
        $point->save();

        ActivityLogger::log('update', $point, 'Map point image uploaded', [
            'village_id' => $map->desa_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => [
                'image_url' => $point->icon_url,
            ]
        ]);
    }
}
