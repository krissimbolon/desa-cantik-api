<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Village; 
use OpenApi\Annotations as OA;

class VillageProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/villages/{id}/profile",
     *     tags={"Villages"},
     *     summary="Get village profile",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile detail",
     *         @OA\JsonContent(type="object", example={"description": "Profil singkat desa", "logo": "storage/village_logos/logo.png"})
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getProfile($id)
    {
        $village = Village::find($id); // Mencari desa berdasarkan ID
        if ($village) {
            return response()->json($village->profile); // Kembalikan profil desa
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/villages/{id}/profile",
     *     tags={"Villages"},
     *     summary="Update village profile",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/VillageCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated",
     *         @OA\JsonContent(type="object", example={"name": "Desa Nonongan Selatan", "location": "Toraja Utara"})
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function updateProfile(Request $request, $id)
    {
        $village = Village::find($id);
        if ($village) {
            // Validasi dan update data profil desa
            $validated = $request->validate([
                'name' => 'required|string',
                'location' => 'required|string',
                // Tambahkan validasi lainnya sesuai kebutuhan
            ]);

            $village->profile()->update($validated); // Update profil desa
            return response()->json($village->profile); // Kembalikan profil yang sudah diupdate
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/villages/{id}/profile/logo",
     *     tags={"Villages"},
     *     summary="Upload village logo",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"logo"},
     *                 @OA\Property(property="logo", type="string", format="binary", description="PNG/JPG/GIF up to 2MB")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo uploaded",
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function uploadLogo(Request $request, $id)
    {
        $village = Village::find($id);
        if ($village) {
            // Validasi file logo
            $request->validate([
                'logo' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            // Upload logo dan simpan
            $path = $request->file('logo')->store('public/village_logos');
            $village->profile->logo = $path; // Simpan path logo di profil desa
            $village->profile->save();

            return response()->json(['message' => 'Logo uploaded successfully', 'logo' => $path]);
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }
}
