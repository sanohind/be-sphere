<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\AppRegistry;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserAppAccessController extends Controller
{
    /**
     * GET /users/{user}/app-access
     * List akses aplikasi user + semua app yang tersedia
     */
    public function index(User $user): JsonResponse
    {
        $accesses = UserAppAccess::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->with('grantor:id,name')
            ->get();

        $grantedAppIds = $accesses->pluck('app_id')->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'email'=> $user->email,
                ],
                'granted_app_ids'  => $grantedAppIds,
                'accesses'         => $accesses,
                // Gunakan AppRegistry sebagai single source of truth
                'available_apps'   => AppRegistry::getForModal(),
            ],
        ]);
    }

    /**
     * POST /users/{user}/app-access
     * Grant akses aplikasi ke user (Superadmin only)
     */
    public function grant(Request $request, User $user): JsonResponse
    {
        $validAppIds = implode(',', AppRegistry::getAllIds());

        $validator = Validator::make($request->all(), [
            'app_id' => 'required|string|in:' . $validAppIds,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $granter = Auth::user();
        $appId   = $request->app_id;

        // Cek apakah sudah ada record (aktif atau revoked)
        $existing = UserAppAccess::where('user_id', $user->id)
            ->where('app_id', $appId)
            ->first();

        if ($existing) {
            if (is_null($existing->revoked_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has access to this application',
                ], 409);
            }
            // Re-grant yang sebelumnya di-revoke
            $existing->update([
                'granted_by' => $granter->id,
                'granted_at' => now(),
                'revoked_at' => null,
            ]);
            $access = $existing->fresh(['grantor:id,name']);
        } else {
            $access = UserAppAccess::create([
                'user_id'    => $user->id,
                'app_id'     => $appId,
                'granted_by' => $granter->id,
                'granted_at' => now(),
            ]);
            $access->load('grantor:id,name');
        }

        AuditLogService::log(
            action: AuditAction::GRANT_APP_ACCESS,
            userId: $granter->id,
            entityType: 'user_app_access',
            entityId: $user->id,
            newValues: ['app_id' => $appId, 'target_user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'message' => "Access to '{$appId}' granted for {$user->name}",
            'data'    => $access,
        ], 201);
    }

    /**
     * DELETE /users/{user}/app-access/{appId}
     * Revoke akses aplikasi dari user (Superadmin only)
     */
    public function revoke(User $user, string $appId): JsonResponse
    {
        $access = UserAppAccess::where('user_id', $user->id)
            ->where('app_id', $appId)
            ->whereNull('revoked_at')
            ->first();

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Active access not found for this application',
            ], 404);
        }

        $revoker = Auth::user();
        $access->update(['revoked_at' => now()]);

        AuditLogService::log(
            action: AuditAction::REVOKE_APP_ACCESS,
            userId: $revoker->id,
            entityType: 'user_app_access',
            entityId: $user->id,
            oldValues: ['app_id' => $appId, 'target_user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'message' => "Access to '{$appId}' revoked from {$user->name}",
        ]);
    }
}
