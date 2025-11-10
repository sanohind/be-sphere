<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Get Users (based on creator permission)
     * 
     * @group User Management
     * @authenticated
     */
    public function index(): JsonResponse
    {
        $creator = Auth::user();
        $users = $this->userService->getUsersByCreator($creator);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Create User
     * 
     * @group User Management
     * @authenticated
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username|max:50',
            'password' => 'required|string|min:6',
            'name' => 'required|string|max:100',
            'nik' => 'nullable|string|unique:users,nik|max:20',
            'phone_number' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $creator = Auth::user();
            $user = $this->userService->createUser(
                $validator->validated(),
                $creator
            );

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get User Detail
     * 
     * @group User Management
     * @authenticated
     */
    public function show(User $user): JsonResponse
    {
        $viewer = Auth::user();
        
        // Check if viewer has permission to see this user
        if ($viewer->isAdmin()) {
            // Admin can only see users in their department
            if (!$viewer->department_id || $user->department_id !== $viewer->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this user',
                ], 403);
            }
        } elseif (!$viewer->isSuperadmin()) {
            // Only superadmin and admin can view user details
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view users',
            ], 403);
        }

        $user->load(['role', 'department', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update User
     * 
     * @group User Management
     * @authenticated
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|unique:users,username,' . $user->id . '|max:50',
            'password' => 'sometimes|string|min:6',
            'name' => 'sometimes|string|max:100',
            'nik' => 'nullable|string|unique:users,nik,' . $user->id . '|max:20',
            'phone_number' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updater = Auth::user();
            $user = $this->userService->updateUser(
                $user,
                $validator->validated(),
                $updater
            );

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete User
     * 
     * @group User Management
     * @authenticated
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $deleter = Auth::user();
            $this->userService->deleteUser($user, $deleter);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get Available Roles (based on creator permission)
     * 
     * @group User Management
     * @authenticated
     */
    public function availableRoles(): JsonResponse
    {
        $creator = Auth::user();
        
        if ($creator->isSuperadmin()) {
            // Superadmin can see all roles
            $roles = Role::where('is_active', true)->get();
        } elseif ($creator->isAdmin()) {
            // Admin can only see operator role
            $roles = Role::where('is_active', true)
                ->where('level', 3)
                ->get();
        } else {
            $roles = collect([]);
        }

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
}