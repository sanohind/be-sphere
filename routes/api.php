<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OIDCController;
use Illuminate\Support\Facades\Route;

// ✅ Test route
Route::get('oidc-test', function() {
    return response()->json(['status' => 'OIDC endpoint working!']);
});

// ✅ OIDC Discovery Endpoint
Route::get('.well-known/openid-configuration', [OIDCController::class, 'discovery'])->where('path', '.*');

// ✅ OAuth Endpoints
Route::group(['prefix' => 'oauth'], function () {
    Route::get('authorize', [OIDCController::class, 'authorize']);
    Route::post('token', [OIDCController::class, 'token']);
    Route::get('userinfo', [OIDCController::class, 'userInfo']);
    Route::get('jwks', [OIDCController::class, 'jwks']);
    Route::get('verify-token', [OIDCController::class, 'verifyOidcToken']); // For AMS backend compatibility
    
    // 🧪 TEST ONLY - Bypass authentication (Development)
    Route::get('authorize-test', [\App\Http\Controllers\Api\OIDCTestController::class, 'authorizeTest']);
});

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Test route for Cloudflare IP detection
Route::get('test-ip', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'ip' => $request->ip(),
        'ips' => $request->ips(),
        'cf_connecting_ip' => $request->header('Cf-Connecting-Ip'),
        'x_forwarded_for' => $request->header('X-Forwarded-For'),
        'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
        'x_forwarded_host' => $request->header('X-Forwarded-Host'),
        'x_forwarded_port' => $request->header('X-Forwarded-Port'),
        'remote_addr' => $request->server('REMOTE_ADDR'),
    ]);
});

// Protected routes
Route::middleware(['jwt.verify', 'user.active'])->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('verify-token', [AuthController::class, 'verifyToken']);
        Route::get('user-info', [AuthController::class, 'userInfo']);
    });

    // Dashboard routes
    Route::get('dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('dashboard/project/{projectId}/url', [\App\Http\Controllers\Api\DashboardController::class, 'getProjectUrl']);

    // Departments (available for authenticated users)
    Route::get('departments', [\App\Http\Controllers\Api\DepartmentController::class, 'index']);

    // User management (only for superadmin and admin)
    Route::middleware('role.level:1,2')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('users/roles/available', [UserController::class, 'availableRoles']);
        
        // Audit logs (only for superadmin and admin)
        Route::get('audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
        Route::get('audit-logs/{auditLog}', [\App\Http\Controllers\Api\AuditLogController::class, 'show']);
        Route::get('audit-logs/filters/actions', [\App\Http\Controllers\Api\AuditLogController::class, 'getActions']);
        Route::get('audit-logs/filters/entity-types', [\App\Http\Controllers\Api\AuditLogController::class, 'getEntityTypes']);
    });

    // Department management (only for superadmin)
    Route::middleware('role.level:1')->group(function () {
        Route::apiResource('departments', \App\Http\Controllers\Api\DepartmentController::class)->except(['index']);
    });
});