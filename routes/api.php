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
    });
});