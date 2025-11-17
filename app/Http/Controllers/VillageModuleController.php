<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Village; 
use OpenApi\Annotations as OA;

class VillageModuleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/villages/{id}/modules",
     *     tags={"Villages"},
     *     summary="List enabled modules for a village",
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
     *         description="Modules for the village",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getModules($id)
    {
        $village = Village::find($id);

        if (!$village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        // Ambil dan kembalikan data modul desa
        return response()->json($village->modules); // Sesuaikan dengan relasi yang ada di model
    }

    /**
     * @OA\Put(
     *     path="/api/villages/{id}/modules/{name}/toggle",
     *     tags={"Villages"},
     *     summary="Toggle a specific module for a village",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Module name",
     *         @OA\Schema(type="string", example="publication")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Module toggled",
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Village or module not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function toggleModule($id, $name)
    {
        $village = Village::find($id);

        if (!$village) {
            return response()->json(['message' => 'Village not found'], 404);
        }

        // Cek jika modul dengan nama tertentu ada di desa
        $module = $village->modules()->where('name', $name)->first();

        if (!$module) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        // Toggle status modul
        $module->status = !$module->status;
        $module->save();

        return response()->json(['message' => 'Module status toggled successfully', 'module' => $module]);
    }
}
