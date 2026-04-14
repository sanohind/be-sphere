<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\OIDCRedirectService;

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
                    'url' => env('FG_STORE_URL', 'http://fg-store.ns1.sanoh.co.id'),
                    'icon' => 'warehouse',
                    'color' => 'blue',
                    'permissions' => ['read', 'write', 'admin'],
                ],
                [
                    'id' => 'ams',
                    'name' => 'Arrival Management System',
                    'description' => 'Arrival management system for incoming goods',
                    'url' => env('AMS_URL', 'https://ams.sanohindonesia.co.id/#/'),
                    'icon' => 'truck',
                    'color' => 'red',
                    'permissions' => ['read', 'write', 'admin'],
                ],
                [
                    'id' => 'arrival-dashboard',
                    'name' => 'Arrival Dashboard (Public)',
                    'description' => 'Arrival Dashboard for public access',
                    'url' => env('AMS_URL', 'https://ams.sanohindonesia.co.id/#/arrival-dashboard'),
                    'icon' => 'arrival',
                    'color' => 'green',
                    'permissions' => ['read'],
                ],
                [
                    'id' => 'arrival-check',
                    'name' => 'Arrival Check (Public)',
                    'description' => 'Arrival Check for driver',
                    'url' => env('AMS_URL', 'https://ams.sanohindonesia.co.id/#/driver'),
                    'icon' => 'driver',
                    'color' => 'yellow',
                    'permissions' => ['read'],
                ],
                [
                    'id' => 'scope',
                    'name' => 'SCOPE (Dashboard)',
                    'description' => 'Inventory and Warehouse management',
                    'url' => env('SCOPE_URL', 'https://scope.sanohindonesia.co.id/#/'),
                    'icon' => 'meeting',
                    'color' => 'purple',
                    'permissions' => ['read', 'write', 'admin'],
                ],
                [
                    'id' => 'cch',
                    'name' => 'CCH',
                    'description' => 'Customer Complaint Handling System',
                    'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                    'icon' => 'qc',
                    'color' => 'orange',
                    'permissions' => ['read', 'write', 'admin'],
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
                        'url' => env('AMS_URL', 'https://ams.sanohindonesia.co.id/#/'),
                        'icon' => 'truck',
                        'color' => 'red',
                        'permissions' => $user->isAdmin() ? ['read', 'write'] : ['read'],
                    ],
                    [
                        'id' => 'arrival-dashboard',
                        'name' => 'Arrival Dashboard (Public)',
                        'description' => 'Arrival Dashboard for public access',
                        'url' => env('ARRIVAL_DASHBOARD_URL', 'https://ams.sanohindonesia.co.id/#/arrival-dashboard'),
                        'icon' => 'arrival',
                        'color' => 'green',
                        'permissions' => ['read'],
                    ],
                    [
                        'id' => 'arrival-check',
                        'name' => 'Arrival Check (Public)',
                        'description' => 'Arrival Check for driver',
                        'url' => env('ARRIVAL_CHECK_URL', 'https://ams.sanohindonesia.co.id/#/driver'),
                        'icon' => 'driver',
                        'color' => 'yellow',
                        'permissions' => ['read'],
                    ],
                    [
                        'id' => 'scope',
                        'name' => 'SCOPE (Dashboard)',
                        'description' => 'Inventory and Warehouse management',
                        'url' => env('SCOPE_URL', 'https://scope.sanohindonesia.co.id/#/'),
                        'icon' => 'meeting',
                        'color' => 'purple',
                        'permissions' => $user->isAdmin() ? ['read', 'write'] : ['read'],
                    ],
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
                'LOG' => [ // Logistics department
                    [
                        'id' => 'fg-store',
                        'name' => 'Finish Good Store',
                        'description' => 'Warehouse management system for finished goods',
                        'url' => env('FG_STORE_URL', 'http://fg-store.ns1.sanoh.co.id'),
                        'icon' => 'warehouse',
                        'color' => 'blue',
                        'permissions' => $user->isAdmin() ? ['read', 'write'] : ['read'],
                    ],
                    [
                        'id' => 'scope',
                        'name' => 'SCOPE (Dashboard)',
                        'description' => 'Inventory and Warehouse management',
                        'url' => env('SCOPE_URL', 'https://scope.sanohindonesia.co.id/#/'),
                        'icon' => 'meeting',
                        'color' => 'purple',
                        'permissions' => $user->isAdmin() ? ['read', 'write'] : ['read'],
                    ],
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
                'PC' => [
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                        [
                        'id' => 'ams',
                        'name' => 'Arrival Management System',
                        'description' => 'Arrival management system for incoming goods',
                        'url' => env('AMS_URL', 'https://ams.sanohindonesia.co.id/#/'),
                        'icon' => 'truck',
                        'color' => 'red',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
                'TOP' => [
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
                'FIN' => [
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
                'QC' => [
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
                'PRD' => [
                    [
                        'id' => 'cch',
                        'name' => 'CCH',
                        'description' => 'Customer Complaint Handling System',
                        'url' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
                        'icon' => 'qc',
                        'color' => 'orange',
                        'permissions' => ['read', 'write', 'admin'],
                    ],
                ],
            ];
            //TESTING

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
            'fg-store' => env('FG_STORE_URL', 'http://fg-store.ns1.sanoh.co.id'),
            'ams' => env('AMS_URL', 'https://ams.sanohindonesia.co.id/#/'),
            'arrival-dashboard' => env('ARRIVAL_DASHBOARD_URL', 'https://ams.sanohindonesia.co.id/#/arrival-dashboard'),
            'arrival-check' => env('ARRIVAL_CHECK_URL', 'https://ams.sanohindonesia.co.id/#/driver'),
            'scope' => env('SCOPE_URL', 'https://scope.sanohindonesia.co.id/#/'),
            'cch' => env('CCH_URL', 'https://cch.sanohindonesia.co.id/#/'),
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

        // Check SSO mode (jwt or oidc)
        $ssoMode = env('SSO_MODE', 'jwt');
        
        // Force OIDC for AMS (Migration Phase 1)
        if ($projectId === 'ams' || $projectId === 'cch') {
            $ssoMode = 'oidc';
        }

        if ($ssoMode === 'oidc') {
            // OIDC Mode: Return authorization URL
            return $this->getOIDCUrl($user, $projectId, $projectUrl);
        } else {
            // JWT Mode (Legacy): Return callback URL with JWT token
            return $this->getJWTUrl($user, $projectId, $projectUrl);
        }

    }

    /**
     * Get OIDC authorization URL (new flow)
     */
    private function getOIDCUrl($user, string $projectId, string $projectUrl): JsonResponse
    {
        try {
            $oidcService = new OIDCRedirectService();
            $clientConfig = $oidcService->getClientConfig($projectId);

            if (!$clientConfig) {
                // Project not configured for OIDC, fallback to JWT mode
                return $this->getJWTUrl($user, $projectId, $projectUrl);
            }

            // Generate temporary token for handover to OIDC controller
            $token = JWTAuth::fromUser($user);

            $authorizationUrl = $oidcService->generateAuthorizationUrl(
                $projectId,
                $clientConfig['client_name'],
                $clientConfig['redirect_uri'],
                $token // Pass token for authentication
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $authorizationUrl,
                    'project_id' => $projectId,
                    'mode' => 'oidc',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate OIDC URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get JWT callback URL (legacy flow)
     */
    private function getJWTUrl($user, string $projectId, string $projectUrl): JsonResponse
    {
        // For authenticated projects, append token
        $token = JWTAuth::fromUser($user);

        // Normalize base URL (remove trailing slash)
        $urlParts = explode('#', $projectUrl);
        $baseUrl = rtrim($urlParts[0], '/');
        $usesHashRouting = str_contains($projectUrl, '#');

        // Build callback path based on routing mode
        $callbackPath = $usesHashRouting ? '/#/sso/callback' : '/sso/callback';
        $urlWithToken = $baseUrl . $callbackPath . '?token=' . $token;

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $urlWithToken,
                'project_id' => $projectId,
                'mode' => 'jwt',
            ],
        ]);
    }
}
