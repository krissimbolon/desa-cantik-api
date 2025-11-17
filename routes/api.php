<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GeospatialDataController;
use App\Http\Controllers\Api\MapPointController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\Api\StatisticTypeController;
use App\Http\Controllers\Api\ThematicMapController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VillageController;
use App\Http\Controllers\Api\VillageModuleController;
use App\Http\Controllers\Api\VillageProfileController;
use App\Http\Controllers\Api\VillageStatisticController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ===============================================
    // PUBLIC AUTHENTICATION ENDPOINTS
    // ===============================================
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('auth/password/reset', [AuthController::class, 'resetPassword']);

    // ===============================================
    // PUBLIC DATA ENDPOINTS
    // ===============================================
    Route::get('statistic-types', [StatisticTypeController::class, 'index']);
    Route::get('dashboard/public', [DashboardController::class, 'public']);

    // Villages (Public)
    Route::get('villages', [VillageController::class, 'index']);
    Route::get('villages/{id}', [VillageController::class, 'show']);
    Route::get('villages/{village_id}/profile', [VillageProfileController::class, 'show']);

    // Geospatial Data (Public reads)
    Route::get('villages/{village_id}/geospatial', [GeospatialDataController::class, 'index']);
    Route::get('villages/{village_id}/geospatial/{geo_id}', [GeospatialDataController::class, 'show']);

    // Thematic Maps (Public reads)
    Route::get('villages/{village_id}/thematic-maps', [ThematicMapController::class, 'index']);
    Route::get('thematic-maps/{map_id}', [ThematicMapController::class, 'show']);



    Route::get('villages/{village}/statistics', [VillageStatisticController::class, 'index']);
    Route::get('villages/{village}/statistics/summary', [VillageStatisticController::class, 'summary']);

    // Export with rate limiting for resource-intensive operations
    Route::get('villages/{village}/statistics/export', [VillageStatisticController::class, 'export'])
        ->middleware('throttle:exports');

    Route::get('villages/{village}/publications', [PublicationController::class, 'index']);
    Route::get('publications/{publication}', [PublicationController::class, 'show']);
    Route::get('publications/{publication}/download', [PublicationController::class, 'download'])
        ->name('publications.download');

    // ===============================================
    // PROTECTED ENDPOINTS (Require Authentication)
    // ===============================================
    Route::middleware('auth:sanctum')->group(function () {
        // User Profile & Authentication
        Route::get('auth/user', [AuthController::class, 'me']);
        Route::put('auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('auth/password', [AuthController::class, 'updatePassword']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logout/all', [AuthController::class, 'logoutAll']);
        Route::post('auth/token/refresh', [AuthController::class, 'refresh']);

        // Profile endpoints (spec 6.4)
        Route::get('profile', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);

        // Dashboard endpoints with explicit role middleware
        Route::get('dashboard/admin', [DashboardController::class, 'admin'])
            ->middleware('role:bps_admin');

        Route::get('dashboard/village', [DashboardController::class, 'village'])
            ->middleware('role:bps_admin,village_officer');

        // User Management (BPS Admin only)
        Route::middleware('role:bps_admin')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::get('users/{id}', [UserController::class, 'show']);
            Route::post('users', [UserController::class, 'store']);
            Route::put('users/{id}', [UserController::class, 'update']);
            Route::delete('users/{id}', [UserController::class, 'destroy']);

            // Activity Logs (BPS Admin only)
            Route::get('activity-logs', [ActivityLogController::class, 'index']);
            Route::get('activity-logs/export', [ActivityLogController::class, 'export']);
            Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);

            // Villages Management (BPS Admin only)
            Route::post('villages', [VillageController::class, 'store']);
            Route::put('villages/{id}', [VillageController::class, 'update']);
            Route::delete('villages/{id}', [VillageController::class, 'destroy']);
            Route::put('villages/{id}/toggle-status', [VillageController::class, 'toggleStatus']);
        });

        // Village Statistics Management (Protected)
        Route::post('villages/{village}/statistics', [VillageStatisticController::class, 'store']);
        Route::put('villages/{village}/statistics/{statistic}', [VillageStatisticController::class, 'update']);
        Route::delete('villages/{village}/statistics/{statistic}', [VillageStatisticController::class, 'destroy']);
        Route::post('villages/{village}/statistics/import', [VillageStatisticController::class, 'import'])
            ->middleware('throttle:imports');

        Route::post('villages/{village}/publications', [PublicationController::class, 'store']);
        Route::put('villages/{village}/publications/{publication}', [PublicationController::class, 'update']);
        Route::post('villages/{village}/publications/{publication}/replace-file', [PublicationController::class, 'replaceFile']);
        Route::delete('villages/{village}/publications/{publication}', [PublicationController::class, 'destroy']);

        // Village Profile Management
        Route::put('villages/{village_id}/profile', [VillageProfileController::class, 'update']);
        Route::post('villages/{village_id}/profile/logo', [VillageProfileController::class, 'uploadLogo']);

        // Geospatial Data Management
        Route::post('villages/{village_id}/geospatial', [GeospatialDataController::class, 'store']);
        Route::put('villages/{village_id}/geospatial/{geo_id}', [GeospatialDataController::class, 'update']);
        Route::delete('villages/{village_id}/geospatial/{geo_id}', [GeospatialDataController::class, 'destroy']);

        // Thematic Maps Management
        Route::post('villages/{village_id}/thematic-maps', [ThematicMapController::class, 'store']);
        Route::put('villages/{village_id}/thematic-maps/{map_id}', [ThematicMapController::class, 'update']);
        Route::delete('villages/{village_id}/thematic-maps/{map_id}', [ThematicMapController::class, 'destroy']);

        // Map Points Management
        Route::post('thematic-maps/{map_id}/points', [MapPointController::class, 'store']);
        Route::put('thematic-maps/{map_id}/points/{point_id}', [MapPointController::class, 'update']);
        Route::delete('thematic-maps/{map_id}/points/{point_id}', [MapPointController::class, 'destroy']);
        Route::post('thematic-maps/{map_id}/points/{point_id}/image', [MapPointController::class, 'uploadImage']);

        // Village Modules Management (BPS Admin only)
        Route::get('villages/{village_id}/modules', [VillageModuleController::class, 'index'])
            ->middleware('role:bps_admin');
        Route::put('villages/{village_id}/modules/{module_name}/toggle', [VillageModuleController::class, 'toggle'])
            ->middleware('role:bps_admin');
    });
});

// Backward compatible village endpoints (without version prefix) for frontend mock expectations
Route::get('villages', [VillageController::class, 'index']);
Route::get('villages/{id}', [VillageController::class, 'show']);

// Legacy endpoint for backward compatibility
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
