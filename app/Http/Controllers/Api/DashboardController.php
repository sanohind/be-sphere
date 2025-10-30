<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardController extends Controller
{
    /**
     * Get dashboard data with available projects
     */
    public function index(): JsonResponse
    {
        $user = JWTAuth::user();
        $user->load(['role', 'department']);

        // Define available projects based on user role
        $projects = $this->getAvailableProjects($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'slug' => $user->role->slug,
                        'level' => $user->role->level,
                    ],
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'name' => $user->department->name,
                        'code' => $user->department->code,
                    ] : null,
                ],
                'projects' => $projects,
            ],
        ]);
    }

    /**
     * Get available projects based on user role
     */
    private function getAvailableProjects($user): array
    {
        $projects = [];

        // Superadmin can access all projects
        if ($user->isSuperadmin()) {
            $projects = [
                [
                    'id' => 'fg-store',
                    'name' => 'Finish Good Store',
                    'description' => 'Warehouse management system for finished goods',
                    'url' => env('FG_STORE_URL', 'http://127.0.0.1:8001'),
                    'icon' => 'warehouse',
                    'color' => 'blue',
                    'permissions' => ['read', 'write', 'admin'],
                ],
                [
                    'id' => 'ams',
                    'name' => 'Arrival Management System',
                    'description' => 'Arrival management system for incoming goods',
                    'url' => env('AMS_URL', 'http://localhost:5174'),
                    'icon' => 'truck',
                    'color' => 'red',
                    'permissions' => ['read', 'write', 'admin'],
                ],
            ];
        }
        // Admin can access department-specific projects
        elseif ($user->isAdmin()) {
            $projects = [
                [
                    'id' => 'fg-store',
                    'name' => 'Finish Good Store',
                    'description' => 'Warehouse management system for finished goods',
                    'url' => env('FG_STORE_URL', 'http://127.0.0.1:8001'),
                    'icon' => 'warehouse',
                    'color' => 'blue',
                    'permissions' => ['read', 'write'],
                ],
            ];
        }
        // Operator can access limited functionality
        elseif ($user->isOperator()) {
            $projects = [
                [
                    'id' => 'fg-store',
                    'name' => 'Finish Good Store',
                    'description' => 'Warehouse operations interface',
                    'url' => env('FG_STORE_URL', 'http://127.0.0.1:8001'),
                    'icon' => 'warehouse',
                    'color' => 'blue',
                    'permissions' => ['read'],
                ],
            ];
        }

        return $projects;
    }

    /**
     * Get project access URL with token
     */
    public function getProjectUrl(string $projectId): JsonResponse
    {
        $user = JWTAuth::user();
        $token = JWTAuth::fromUser($user);

        $projectUrls = [
            'fg-store' => env('FG_STORE_URL', 'http://127.0.0.1:8001'),
            'ams' => env('AMS_URL', 'http://localhost:5174'),
        ];

        if (!isset($projectUrls[$projectId])) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $projectUrl = $projectUrls[$projectId];
        $urlWithToken = $projectUrl . '/sso/callback?token=' . $token;

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $urlWithToken,
                'project_id' => $projectId,
            ],
        ]);
    }
}
