<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Village;
use App\Models\VillageProfile;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class VillageProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/villages/{village_id}/profile",
     *     tags={"Village Profile"},
     *     summary="Get village profile (Public)",
     *     @OA\Parameter(
     *         name="village_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Village not found")
     * )
     */
    public function show($villageId): JsonResponse
    {
        $village = Village::with('profile')->findOrFail($villageId);

        if (!$village->profile) {
            return response()->json([
                'success' => false,
                'message' => 'Village profile not found'
            ], 404);
        }

        $profile = $village->profile;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $profile->id,
                'village_id' => $profile->village_id,
                'description' => $profile->deskripsi,
                'vision' => $profile->visi,
                'mission' => $profile->misi ? json_decode($profile->misi, true) : [],
                'area' => $profile->area,
                'population' => $profile->population,
                'population_density' => $profile->population_density,
                'address' => $profile->address,
                'phone' => $profile->phone,
                'email' => $profile->email,
                'website' => $profile->website,
                'logo_url' => $profile->logo_url,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/villages/{village_id}/profile",
     *     tags={"Village Profile"},
     *     summary="Update village profile",
     *     description="Update village profile (BPS Admin or Village Officer for own village)",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Profile updated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(Request $request, $villageId): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this village profile'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|string',
            'vision' => 'sometimes|string',
            'mission' => 'sometimes|array',
            'area' => 'sometimes|numeric|min:0',
            'population' => 'sometimes|integer|min:0',
            'address' => 'sometimes|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'logo_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get or create profile
        $profile = $village->profile;
        if (!$profile) {
            $profile = new VillageProfile(['village_id' => $village->id]);
        }

        $oldData = $profile->toArray();

        // Map spec fields to model fields
        if ($request->has('description')) $profile->deskripsi = $request->description;
        if ($request->has('vision')) $profile->visi = $request->vision;
        if ($request->has('mission')) $profile->misi = json_encode($request->mission);
        if ($request->has('area')) $profile->area = $request->area;
        if ($request->has('population')) $profile->population = $request->population;
        if ($request->has('address')) $profile->address = $request->address;
        if ($request->has('phone')) $profile->phone = $request->phone;
        if ($request->has('email')) $profile->email = $request->email;
        if ($request->has('website')) $profile->website = $request->website;
        if ($request->has('logo_url')) $profile->logo_url = $request->logo_url;

        $profile->updated_by = $user->id;

        // Calculate population density if both area and population are set
        if ($profile->area > 0 && $profile->population > 0) {
            $profile->population_density = $profile->population / $profile->area;
        }

        $profile->save();

        ActivityLogger::log('update', $profile, 'Village profile updated', [
            'old_data' => $oldData,
            'new_data' => $profile->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Village profile updated successfully',
            'data' => [
                'id' => $profile->id,
                'village_id' => $profile->village_id,
                'description' => $profile->deskripsi,
                'vision' => $profile->visi,
                'mission' => $profile->misi ? json_decode($profile->misi, true) : [],
                'area' => $profile->area,
                'population' => $profile->population,
                'population_density' => $profile->population_density,
                'address' => $profile->address,
                'phone' => $profile->phone,
                'email' => $profile->email,
                'website' => $profile->website,
                'logo_url' => $profile->logo_url,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/villages/{village_id}/profile/logo",
     *     tags={"Village Profile"},
     *     summary="Upload village logo",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Logo uploaded"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function uploadLogo(Request $request, $villageId): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        // Check authorization
        $user = $request->user();
        $userRole = $user->role?->role_name;

        if ($userRole === 'village_officer' && $user->village_id !== $village->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this village profile'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|file|mimes:jpeg,jpg,png|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $village->profile;
        if (!$profile) {
            $profile = VillageProfile::create(['village_id' => $village->id]);
        }

        // Delete old logo if exists
        if ($profile->logo_url && Storage::exists($profile->logo_url)) {
            Storage::delete($profile->logo_url);
        }

        // Store new logo
        $path = $request->file('logo')->store('village-logos', 'public');
        $profile->logo_url = Storage::url($path);
        $profile->updated_by = $user->id;
        $profile->save();

        ActivityLogger::log('update', $profile, 'Village logo uploaded');

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'data' => [
                'logo_url' => $profile->logo_url,
            ]
        ]);
    }
}
