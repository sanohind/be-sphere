<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OIDCController;
use App\Http\Controllers\Api\UserAppAccessController;
use Illuminate\Support\Facades\Route;

// ✅ Test route
Route::get('oidc-test', function() {
    return response()->json(['status' => 'OIDC endpoint working!']);
});

// ✅ OIDC Discovery Endpoint
Route::get('.well-known/openid-configuration', [OIDCController::class, 'discovery'])->where('path', '.*');

// ✅ OAuth Endpoints
Route::group(['prefix' => 'oauth'], function () {
    Route::get('authorize', [OIDCController::class, 'authorizeClient']);
    Route::post('token', [OIDCController::class, 'token']);
    Route::get('userinfo', [OIDCController::class, 'userInfo']);
    Route::get('jwks', [OIDCController::class, 'jwks']);
    Route::get('verify-token', [OIDCController::class, 'verifyOidcToken']);

    // 🧪 TEST ONLY
    Route::get('authorize-test', [\App\Http\Controllers\Api\OIDCTestController::class, 'authorizeTest']);
});

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // Public: set password dari link email (tidak perlu JWT)
    Route::post('set-password', [AuthController::class, 'setPassword']);
});

// Test route for Cloudflare IP detection
Route::get('test-ip', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'ip'               => $request->ip(),
        'cf_connecting_ip' => $request->header('Cf-Connecting-Ip'),
        'x_forwarded_for'  => $request->header('X-Forwarded-For'),
    ]);
});

// Protected routes
Route::middleware(['jwt.verify', 'user.active'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('verify-token', [AuthController::class, 'verifyToken']);
        Route::get('user-info', [AuthController::class, 'userInfo']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('avatar', [AuthController::class, 'updateAvatar']);
    });

    // Dashboard routes
    Route::get('dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('dashboard/project/{projectId}/url', [\App\Http\Controllers\Api\DashboardController::class, 'getProjectUrl']);

    // Departments (available for authenticated users)
    Route::get('departments', [\App\Http\Controllers\Api\DepartmentController::class, 'index']);

    // User management (superadmin + admin)
    Route::middleware('role.level:1,2')->group(function () {
        // Custom routes MUST come before apiResource to avoid {user} wildcard capturing them
        Route::get('users/roles/available', [UserController::class, 'availableRoles']);
        Route::post('users/{user}/send-reset-password', [UserController::class, 'sendResetPassword']);

        Route::apiResource('users', UserController::class);

        // Audit logs
        Route::get('audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
        Route::get('audit-logs/{auditLog}', [\App\Http\Controllers\Api\AuditLogController::class, 'show']);
        Route::get('audit-logs/filters/actions', [\App\Http\Controllers\Api\AuditLogController::class, 'getActions']);
        Route::get('audit-logs/filters/entity-types', [\App\Http\Controllers\Api\AuditLogController::class, 'getEntityTypes']);
    });

    // Department management (superadmin only)
    Route::middleware('role.level:1')->group(function () {
        Route::apiResource('departments', \App\Http\Controllers\Api\DepartmentController::class)->except(['index']);

        // App Access management (Superadmin only)
        Route::get('users/{user}/app-access', [UserAppAccessController::class, 'index']);
        Route::post('users/{user}/app-access', [UserAppAccessController::class, 'grant']);
        Route::delete('users/{user}/app-access/{appId}', [UserAppAccessController::class, 'revoke']);
    });
});