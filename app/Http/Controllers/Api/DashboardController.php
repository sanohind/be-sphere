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
     * Get available projects based on user role and department
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
                [
                    'id' => 'arrival-dashboard',
                    'name' => 'Arrival Dashboard (Public)',
                    'description' => 'Arrival Dashboard for public access',
                    'url' => env('ARRIVAL_DASHBOARD_URL', 'http://localhost:5174/arrival-dashboard'),
                    'icon' => 'arrival',
                    'color' => 'green',
                    'permissions' => ['read'],
                ],
                [
                    'id' => 'arrival-check',
                    'name' => 'Arrival Check (Public)',
                    'description' => 'Arrival Check for driver',
                    'url' => env('ARRIVAL_CHECK_URL', 'http://localhost:5174/driver'),
                    'icon' => 'driver',
                    'color' => 'yellow',
                    'permissions' => ['read'],
                ],
            ];
        }
        // Other users (Admin/Operator) access projects based on their department
        else {
            $departmentCode = $user->department?->code;

            // Define department-based project mapping
            $departmentProjects = [
                'WH' => [ // Warehouse department
                    [
                        'id' => 'ams',
                        'name' => 'Arrival Management System',
                        'description' => 'Arrival management system for incoming goods',
                        'url' => env('AMS_URL', 'http://localhost:5174'),
                        'icon' => 'truck',
                        'color' => 'red',
                        'permissions' => $user->isAdmin() ? ['read', 'write'] : ['read'],
                    ],
                    [
                        'id' => 'arrival-dashboard',
                        'name' => 'Arrival Dashboard (Public)',
                        'description' => 'Arrival Dashboard for public access',
                        'url' => env('ARRIVAL_DASHBOARD_URL', 'http://localhost:5174/arrival-dashboard'),
                        'icon' => 'arrival',
                        'color' => 'green',
                        'permissions' => ['read'],
                    ],
                    [
                        'id' => 'arrival-check',
                        'name' => 'Arrival Check (Public)',
                        'description' => 'Arrival Check for driver',
                        'url' => env('ARRIVAL_CHECK_URL', 'http://localhost:5174/driver'),
                        'icon' => 'driver',
                        'color' => 'yellow',
                        'permissions' => ['read'],
                    ],
                ],
                'LOG' => [ // Logistics department
                    [
                        'id' => 'fg-store',
                        'name' => 'Finish Good Store',
                        'description' => 'Warehouse management system for finished goods',
                        'url' => env('FG_STORE_URL', 'http://127.0.0.1:8001'),
                        'icon' => 'warehouse',
                        'color' => 'blue',
                        'permissions' => $user->isAdmin() ? ['read', 'write'] : ['read'],
                    ],
                ],
            ];

            // Get projects based on department code
            if ($departmentCode && isset($departmentProjects[$departmentCode])) {
                $projects = $departmentProjects[$departmentCode];
            }
        }

        return $projects;
    }

    /**
     * Get project access URL with token
     */
    public function getProjectUrl(string $projectId): JsonResponse
    {
        $user = JWTAuth::user();
        $user->load(['role', 'department']);
        
        // Check if user has access to this project
        $availableProjects = $this->getAvailableProjects($user);
        $hasAccess = collect($availableProjects)->contains('id', $projectId);

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this project'
            ], 403);
        }

        // Define public projects that don't require authentication
        $publicProjects = ['arrival-dashboard', 'arrival-check'];

        // Define project URLs
        $projectUrls = [
            'fg-store' => env('FG_STORE_URL', 'http://127.0.0.1:8001'),
            'ams' => env('AMS_URL', 'http://localhost:5174'),
            'arrival-dashboard' => env('ARRIVAL_DASHBOARD_URL', 'http://localhost:5174/arrival-dashboard'),
            'arrival-check' => env('ARRIVAL_CHECK_URL', 'http://localhost:5174/driver'),
        ];

        if (!isset($projectUrls[$projectId])) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $projectUrl = $projectUrls[$projectId];

        // For public projects, return URL directly without token
        if (in_array($projectId, $publicProjects)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $projectUrl,
                    'project_id' => $projectId,
                ],
            ]);
        }

        // For authenticated projects, append token
        $token = JWTAuth::fromUser($user);
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
