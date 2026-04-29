<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAppAccess;
use App\Services\AppRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\OIDCRedirectService;

class DashboardController extends Controller
{
    /**
     * Get single app config by ID — delegasi ke AppRegistry
     */
    private function getAppConfig(string $appId): ?array
    {
        return AppRegistry::findById($appId);
    }

    /**
     * Get dashboard data with available projects
     */
    public function index(): JsonResponse
    {
        $user = JWTAuth::user();
        $user->load(['role', 'department']);

        $projects = $this->getAvailableProjects($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'username'         => $user->username,
                    'nik'              => $user->nik,
                    'phone_number'     => $user->phone_number,
                    'avatar'           => $user->avatar ? asset('storage/' . $user->avatar) : null,
                    'is_active'        => $user->is_active,
                    'last_login_at'    => $user->last_login_at,
                    'email_verified_at'=> $user->email_verified_at,
                    'created_at'       => $user->created_at,
                    'role'             => [
                        'id'    => $user->role->id,
                        'name'  => $user->role->name,
                        'slug'  => $user->role->slug,
                        'level' => $user->role->level,
                    ],
                    'department' => $user->department ? [
                        'id'   => $user->department->id,
                        'name' => $user->department->name,
                        'code' => $user->department->code,
                    ] : null,
                ],
                'projects' => $projects,
            ],
        ]);
    }

    /**
     * Get available projects:
     * - Superadmin → bypass, selalu dapat semua app
     * - User lain → query tabel user_app_access
     */
    private function getAvailableProjects($user): array
    {
        // Superadmin: bypass — selalu lihat semua aplikasi
        if ($user->isSuperadmin()) {
            return AppRegistry::getAll();
        }

        // User biasa: ambil dari tabel user_app_access
        return UserAppAccess::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->get()
            ->map(fn($access) => $this->getAppConfig($access->app_id))
            ->filter()
            ->values()
            ->toArray();
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
                'message' => 'You do not have access to this project',
            ], 403);
        }

        $appConfig = $this->getAppConfig($projectId);
        if (!$appConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        $projectUrl = $appConfig['url'];

        // Public projects: return URL directly without token
        $publicProjects = ['arrival-dashboard', 'arrival-check'];
        if (in_array($projectId, $publicProjects)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'url'        => $projectUrl,
                    'project_id' => $projectId,
                ],
            ]);
        }

        // SSO mode: oidc or jwt
        $ssoMode = env('SSO_MODE', 'jwt');

        // Force OIDC for AMS and CCH (Migration Phase 1)
        if ($projectId === 'ams' || $projectId === 'cch') {
            $ssoMode = 'oidc';
        }

        if ($ssoMode === 'oidc') {
            return $this->getOIDCUrl($user, $projectId, $projectUrl);
        }

        return $this->getJWTUrl($user, $projectId, $projectUrl);
    }

    /**
     * Get OIDC authorization URL
     */
    private function getOIDCUrl($user, string $projectId, string $projectUrl): JsonResponse
    {
        try {
            $oidcService   = new OIDCRedirectService();
            $clientConfig  = $oidcService->getClientConfig($projectId);

            if (!$clientConfig) {
                return $this->getJWTUrl($user, $projectId, $projectUrl);
            }

            $token = JWTAuth::fromUser($user);
            $authorizationUrl = $oidcService->generateAuthorizationUrl(
                $projectId,
                $clientConfig['client_name'],
                $clientConfig['redirect_uri'],
                $token
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'url'        => $authorizationUrl,
                    'project_id' => $projectId,
                    'mode'       => 'oidc',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate OIDC URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get JWT callback URL (legacy)
     */
    private function getJWTUrl($user, string $projectId, string $projectUrl): JsonResponse
    {
        $token    = JWTAuth::fromUser($user);
        $urlParts = explode('#', $projectUrl);
        $baseUrl  = rtrim($urlParts[0], '/');

        // Define which apps strictly use HashRouting
        $hashRoutingApps = ['scope', 'ams', 'cch'];
        $usesHashRouting = str_contains($projectUrl, '#') || in_array($projectId, $hashRoutingApps);

        $callbackPath = $usesHashRouting ? '/#/sso/callback' : '/sso/callback';
        $urlWithToken = $baseUrl . $callbackPath . '?token=' . $token;

        return response()->json([
            'success' => true,
            'data' => [
                'url'        => $urlWithToken,
                'project_id' => $projectId,
                'mode'       => 'jwt',
            ],
        ]);
    }
}
