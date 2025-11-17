<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Village; 
use OpenApi\Annotations as OA;

class VillageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/villages",
     *     tags={"Villages"},
     *     summary="List all villages",
     *     @OA\Response(
     *         response=200,
     *         description="Array of villages",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/VillageResource"))
     *     )
     * )
     */
    public function getAll()
    {
        $villages = Village::with('profile')
            ->orderBy('name')
            ->get()
            ->map(fn(Village $village) => $this->formatVillageResponse($village));

        return response()->json($villages);
    }

    /**
     * @OA\Get(
     *     path="/api/villages/{id}",
     *     tags={"Villages"},
     *     summary="Get village detail",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Village found",
     *         @OA\JsonContent(ref="#/components/schemas/VillageResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getDetail($id)
    {
        $village = Village::with('profile')->find($id); // Mencari desa berdasarkan ID
        if ($village) {
            return response()->json($this->formatVillageResponse($village));
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/villages",
     *     tags={"Villages"},
     *     summary="Create a new village",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/VillageCreateRequest")),
     *     @OA\Response(
     *         response=201,
     *         description="Village created",
     *         @OA\JsonContent(ref="#/components/schemas/VillageResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'location' => 'required|string',
            // Validasi data lainnya
        ]);

        $village = Village::create($validated); // Menyimpan desa baru ke database
        return response()->json($village, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/villages/{id}",
     *     tags={"Villages"},
     *     summary="Update a village",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/VillageCreateRequest")),
     *     @OA\Response(
     *         response=200,
     *         description="Village updated",
     *         @OA\JsonContent(ref="#/components/schemas/VillageResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $village = Village::find($id);
        if ($village) {
            $village->update($request->all()); // Update data desa
            return response()->json($village);
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/villages/{id}",
     *     tags={"Villages"},
     *     summary="Delete a village",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Village deleted",
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function delete($id)
    {
        $village = Village::find($id);
        if ($village) {
            $village->delete(); // Menghapus desa
            return response()->json(['message' => 'Village deleted']);
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/villages/{id}/toggle-status",
     *     tags={"Villages"},
     *     summary="Toggle village visibility status",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status toggled",
     *         @OA\JsonContent(ref="#/components/schemas/VillageResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function toggleStatus($id)
    {
        $village = Village::find($id);
        if ($village) {
            $village->is_active = !$village->is_active; // Toggle status aktif
            $village->save();
            return response()->json($village);
        } else {
            return response()->json(['message' => 'Village not found'], 404);
        }
    }

    private function formatVillageResponse(Village $village): array
    {
        $profile = $village->profile;
        $image = $profile?->thumbnail_url
            ?? $profile?->foto_url
            ?? $village->logo_url
            ?? 'https://placehold.co/800x600/1C6EA4/FFFFFF?text=Desa+Cantik';

        $area = $profile?->area;

        return [
            'id' => (string) $village->id,
            'name' => $village->name,
            'district' => $village->kecamatan,
            'regency' => $village->kabupaten,
            'province' => $village->provinsi,
            'population' => (int) ($profile?->population ?? 0),
            'status' => $village->is_visible ? 'Aktif' : 'Tidak Aktif',
            'image' => $image,
            'area' => $area !== null ? (float) $area : 1.0,
            'households' => (int) ($profile?->households ?? 0),
            'malePopulation' => (int) ($profile?->male_population ?? 0),
            'femalePopulation' => (int) ($profile?->female_population ?? 0),
        ];
    }
}
