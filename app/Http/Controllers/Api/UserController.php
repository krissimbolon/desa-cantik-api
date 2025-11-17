<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Village;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Get all users",
     *     description="Retrieve paginated list of users with optional filters (BPS Admin only)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="village_id",
     *         in="query",
     *         description="Filter by village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by full_name, username, or email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = User::query()
            ->with(['role:id,role_name,display_name', 'village:id,name,code'])
            ->select(['id', 'username', 'email', 'full_name', 'phone_number', 'role_id', 'village_id', 'is_active', 'created_at', 'updated_at']);

        // Apply filters
        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('role_name', $request->query('role'));
            });
        }

        if ($request->filled('village_id')) {
            $query->where('village_id', $request->query('village_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get user detail",
     *     description="Get detailed information about a specific user (BPS Admin only)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $user = User::with(['role:id,role_name,display_name', 'village:id,name,code,district'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone' => $user->phone_number,
                'role' => [
                    'id' => $user->role->id,
                    'role_name' => $user->role->role_name,
                    'display_name' => $user->role->display_name,
                ],
                'village' => $user->village ? [
                    'id' => $user->village->id,
                    'name' => $user->village->name,
                    'code' => $user->village->code,
                    'district' => $user->village->district,
                ] : null,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create new user",
     *     description="Create a new user account (BPS Admin only)",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent()),
     *     @OA\Response(response=201, description="User created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'full_name' => 'required|string|max:255',
            'role' => ['required', Rule::in([UserRole::BPS_ADMIN, UserRole::VILLAGE_OFFICER])],
            'village_id' => 'required_if:role,' . UserRole::VILLAGE_OFFICER . '|nullable|exists:villages,id',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get role_id from role_name
        $role = UserRole::where('role_name', $request->role)->firstOrFail();

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'role_id' => $role->id,
            'village_id' => $request->village_id,
            'phone_number' => $request->phone,
            'is_active' => true,
        ]);

        $user->load(['role:id,role_name,display_name', 'village:id,name,code']);

        ActivityLogger::log('create', $user, 'User account created');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone' => $user->phone_number,
                'role' => [
                    'id' => $user->role->id,
                    'role_name' => $user->role->role_name,
                    'display_name' => $user->role->display_name,
                ],
                'village' => $user->village ? [
                    'id' => $user->village->id,
                    'name' => $user->village->name,
                    'code' => $user->village->code,
                ] : null,
                'is_active' => $user->is_active,
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update user",
     *     description="Update user information (BPS Admin only)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User updated successfully"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:100|unique:users,username,' . $id,
            'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
            'full_name' => 'sometimes|string|max:255',
            'role' => ['sometimes', Rule::in([UserRole::BPS_ADMIN, UserRole::VILLAGE_OFFICER])],
            'village_id' => 'sometimes|nullable|exists:villages,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $user->toArray();

        if ($request->has('username')) $user->username = $request->username;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('full_name')) $user->full_name = $request->full_name;
        if ($request->has('role')) {
            $role = UserRole::where('role_name', $request->role)->firstOrFail();
            $user->role_id = $role->id;
        }
        if ($request->has('village_id')) $user->village_id = $request->village_id;
        if ($request->has('phone')) $user->phone_number = $request->phone;
        if ($request->has('is_active')) $user->is_active = $request->is_active;

        $user->save();
        $user->load(['role:id,role_name,display_name', 'village:id,name,code']);

        ActivityLogger::log('update', $user, 'User account updated', [
            'old_data' => $oldData,
            'new_data' => $user->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone' => $user->phone_number,
                'role' => [
                    'id' => $user->role->id,
                    'role_name' => $user->role->role_name,
                    'display_name' => $user->role->display_name,
                ],
                'village' => $user->village ? [
                    'id' => $user->village->id,
                    'name' => $user->village->name,
                    'code' => $user->village->code,
                ] : null,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete user",
     *     description="Delete a user account (BPS Admin only, cannot delete self)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User deleted successfully"),
     *     @OA\Response(response=403, description="Cannot delete own account"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent deleting own account
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        ActivityLogger::log('delete', $user, 'User account deleted', [
            'old_data' => $user->toArray(),
        ]);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
