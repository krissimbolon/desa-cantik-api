<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Village;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class VillageModuleController extends Controller
{
    /**
     * Get all modules for a village (Public)
     */
    public function index($villageId): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        $modules = Module::where('village_id', $village->id)
            ->get()
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'village_id' => $module->village_id,
                    'module_name' => $module->name,
                    'is_enabled' => $module->status === 'active',
                    'created_at' => $module->created_at,
                    'updated_at' => $module->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $modules
        ]);
    }

    /**
     * Toggle module status (BPS Admin only)
     */
    public function toggle(Request $request, $villageId, $moduleName): JsonResponse
    {
        $village = Village::findOrFail($villageId);

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find or create module
        $module = Module::firstOrNew([
            'village_id' => $village->id,
            'name' => $moduleName,
        ]);

        $oldStatus = $module->status;
        $module->status = $request->is_enabled ? 'active' : 'inactive';
        $module->save();

        ActivityLogger::log('update', $module, 'Village module toggled', [
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $module->status],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Module status updated successfully',
            'data' => [
                'id' => $module->id,
                'village_id' => $module->village_id,
                'module_name' => $module->name,
                'is_enabled' => $module->status === 'active',
            ]
        ]);
    }
}
