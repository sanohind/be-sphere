<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\User;
use App\Models\UserSession;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(array $credentials, Request $request): array
    {
        // Find user
        $user = User::where('email', $credentials['email'])
            ->orWhere('username', $credentials['email'])
            ->first();

        if (!$user) {
            AuditLogService::log(
                action: AuditAction::FAILED_LOGIN,
                userId: null,
                request: $request
            );
            
            throw new \Exception('Invalid credentials', 401);
        }

        // Check password
        if (!Hash::check($credentials['password'], $user->password)) {
            AuditLogService::log(
                action: AuditAction::FAILED_LOGIN,
                userId: $user->id,
                request: $request
            );
            
            throw new \Exception('Invalid credentials', 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            throw new \Exception('Your account has been deactivated', 403);
        }

        // Check if role is active
        if (!$user->role->is_active) {
            throw new \Exception('Your role has been deactivated', 403);
        }

        // Generate tokens
        $token = JWTAuth::fromUser($user);
        $refreshToken = $this->generateRefreshToken($user);

        // Create session
        $payload = JWTAuth::setToken($token)->getPayload();
        $this->createSession($user, $payload->get('jti'), $request);

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log audit
        AuditLogService::log(
            action: AuditAction::LOGIN,
            userId: $user->id,
            request: $request
        );

        return [
            'user' => $this->getUserData($user),
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60, // in seconds
        ];
    }

    public function logout(Request $request): void
    {
        $user = Auth::user();
        $token = JWTAuth::getToken();
        $payload = JWTAuth::getPayload($token);

        // Deactivate session
        UserSession::where('token_jti', $payload->get('jti'))
            ->update([
                'logout_at' => now(),
                'is_active' => false,
            ]);

        // Invalidate token
        JWTAuth::invalidate($token);

        // Log audit
        AuditLogService::log(
            action: AuditAction::LOGOUT,
            userId: $user->id,
            request: $request
        );
    }

    public function refresh(string $refreshToken): array
    {
        $token = RefreshToken::where('token', $refreshToken)->first();

        if (!$token || $token->isExpired()) {
            throw new \Exception('Invalid or expired refresh token', 401);
        }

        $user = $token->user;

        if (!$user->is_active) {
            throw new \Exception('Your account has been deactivated', 403);
        }

        // Generate new tokens
        $accessToken = JWTAuth::fromUser($user);
        $newRefreshToken = $this->generateRefreshToken($user);

        // Delete old refresh token
        $token->delete();

        // Log audit
        AuditLogService::log(
            action: AuditAction::REFRESH_TOKEN,
            userId: $user->id
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
        ];
    }

    public function verifyToken(): array
    {
        $user = Auth::user();

        // Log audit
        AuditLogService::log(
            action: AuditAction::VERIFY_TOKEN,
            userId: $user->id
        );

        return [
            'valid' => true,
            'user' => $this->getUserData($user),
        ];
    }

    private function generateRefreshToken(User $user): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(30); // 30 days

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    private function createSession(User $user, string $jti, Request $request): void
    {
        UserSession::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'token_jti' => $jti,
            'login_at' => now(),
            'expires_at' => now()->addMinutes((int) config('jwt.ttl')),
            'is_active' => true,
        ]);
    }

    private function getUserData(User $user): array
    {
        $user->load(['role', 'department']);

        return [
            'id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'name' => $user->name,
            'nik' => $user->nik,
            'phone_number' => $user->phone_number,
            'avatar' => $user->avatar,
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
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
        ];
    }
}