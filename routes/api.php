<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
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
});