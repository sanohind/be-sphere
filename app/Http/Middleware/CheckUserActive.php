<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ], 403);
        }

        // Check if role is active
        if (!$user->role->is_active) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your role has been deactivated. Please contact administrator.'
            ], 403);
        }

        // Check if department is active (if user has department)
        if ($user->department_id && !$user->department->is_active) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your department has been deactivated. Please contact administrator.'
            ], 403);
        }

        return $next($request);
    }
}