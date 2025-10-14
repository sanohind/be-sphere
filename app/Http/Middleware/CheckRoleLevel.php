<?php

namespace App\Http\Middleware;

use App\Enums\RoleLevel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRoleLevel
{
    /**
     * Handle an incoming request.
     * 
     * @param int|array $levels - RoleLevel values (1,2,3,4)
     * Usage in routes: ->middleware('role.level:1,2')
     */
    public function handle(Request $request, Closure $next, ...$levels): Response
    {
        $user = Auth::user();
        
        if (!in_array($user->role->level, array_map('intval', $levels))) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}