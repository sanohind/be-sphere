<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\AuditAction;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Login
     * 
     * @group Authentication
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string', // can be email or username
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->authService->login(
                $validator->validated(),
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Logout
     * 
     * @group Authentication
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request);

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh Token
     * 
     * @group Authentication
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->authService->refresh($request->refresh_token);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Verify Token
     * 
     * @group Authentication
     * @authenticated
     */
    public function verifyToken(): JsonResponse
    {
        try {
            $result = $this->authService->verifyToken();

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get User Info
     * 
     * @group Authentication
     * @authenticated
     */
    public function userInfo(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['role', 'department', 'creator']);

        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $user->id,
                'email'            => $user->email,
                'username'         => $user->username,
                'name'             => $user->name,
                'nik'              => $user->nik,
                'phone_number'     => $user->phone_number,
                'avatar'           => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'role'             => [
                    'id'    => $user->role->id,
                    'name'  => $user->role->name,
                    'slug'  => $user->role->slug,
                    'level' => $user->role->level,
                ],
                'department'       => $user->department ? [
                    'id'   => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                ] : null,
                'created_by'       => $user->creator ? [
                    'id'   => $user->creator->id,
                    'name' => $user->creator->name,
                ] : null,
                'is_active'        => $user->is_active,
                'email_verified_at'=> $user->email_verified_at,
                'last_login_at'    => $user->last_login_at,
                'created_at'       => $user->created_at,
            ],
        ]);
    }

    /**
     * Set Password (dari link email welcome)
     * Public endpoint — tidak butuh JWT
     *
     * @group Authentication
     */
    public function setPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token'                 => 'required|string',
            // Kriteria: min 8 karakter, huruf besar, huruf kecil, angka, simbol
            'password'              => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',       // minimal 1 huruf kapital
                'regex:/[a-z]/',       // minimal 1 huruf kecil
                'regex:/[0-9]/',       // minimal 1 angka
                'regex:/[^A-Za-z0-9]/', // minimal 1 simbol
            ],
            'password_confirmation' => 'required|string',
        ], [
            'password.regex'    => 'Password must contain uppercase letters, lowercase letters, numbers, and symbols.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.confirmed'=> 'Password confirmation does not match.',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Cari user berdasarkan token
        $user = User::where('password_reset_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Link tidak valid atau sudah digunakan.',
            ], 400);
        }

        // Cek apakah token sudah expired
        if ($user->password_reset_expires_at && $user->password_reset_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Link sudah kadaluarsa. Hubungi administrator untuk mendapatkan link baru.',
            ], 400);
        }

        // Update password dan hapus token
        $user->update([
            'password'                  => Hash::make($request->password),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
            'email_verified_at'         => $user->email_verified_at ?? now(),
        ]);

        // Log audit
        AuditLogService::log(
            action: AuditAction::SET_PASSWORD,
            userId: $user->id,
            entityType: 'user',
            entityId: $user->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil dibuat. Silakan login dengan password baru Anda.',
        ]);
    }

    /**
     * Update profile for the currently authenticated user.
     * Only name, email, and phone_number are editable.
     *
     * @group Authentication
     * @authenticated
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:100',
            'email'        => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|nullable|string|max:20',
            'nik'          => 'sometimes|nullable|string|max:20|unique:users,nik,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->update($validator->validated());

        AuditLogService::log(
            action: AuditAction::UPDATE_USER,
            userId: $user->id,
            entityType: 'user',
            entityId: $user->id,
            newValues: $validator->validated(),
        );

        $userResponse = $user->fresh()->load(['role', 'department'])->toArray();
        $userResponse['avatar'] = $user->avatar ? asset('storage/' . $user->avatar) : null;

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => $userResponse,
        ]);
    }

    /**
     * Upload avatar for the currently authenticated user.
     * Accepts a base64-encoded cropped image (JPEG/PNG/WebP).
     *
     * @group Authentication
     * @authenticated
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|string', // base64 data URL
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Avatar data is required',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $dataUrl = $request->input('avatar');

        // Parse base64 data URL: "data:image/png;base64,..."
        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/', $dataUrl, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid image format. Only PNG, JPEG, and WebP are supported.',
            ], 422);
        }

        $imageData = base64_decode($matches[2]);
        if ($imageData === false) {
            return response()->json(['success' => false, 'message' => 'Failed to decode image data.'], 422);
        }

        // Limit file size: 2MB
        if (strlen($imageData) > 2 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'Image size must not exceed 2MB.'], 422);
        }

        // Run background removal via Python library (backgroundremover)
        $tempInput = storage_path('app/temp_avatar_in_' . time() . '.png');
        $tempOutput = storage_path('app/temp_avatar_out_' . time() . '.png');
        
        file_put_contents($tempInput, $imageData);

        // Run the backgroundremover command using python module syntax to avoid PATH issues
        // Using escapeshellarg to prevent command injection
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $pythonCmd = $isWindows ? 'python' : 'python3';
        
        if (!$isWindows) {
            putenv('NUMBA_CACHE_DIR=/tmp');
            putenv('U2NET_HOME=/var/www/.u2net');
        }

        $cmd = $pythonCmd . " -m backgroundremover.cmd.cli -i " . escapeshellarg($tempInput) . " -o " . escapeshellarg($tempOutput) . " 2>&1";
        exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && file_exists($tempOutput)) {
            $processedData = file_get_contents($tempOutput);
            if ($processedData) {
                $imageData = $processedData;
            }
        } else {
            \Illuminate\Support\Facades\Log::error('Backgroundremover failed: ' . implode("\n", $output));
            // Silently fall back to original uploaded image
        }

        // Cleanup temporary files
        @unlink($tempInput);
        @unlink($tempOutput);

        // Delete old avatar file if it's a stored file (not a default)
        if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $filename = 'avatars/' . $user->id . '_' . time() . '.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageData);

        $user->update(['avatar' => $filename]);

        AuditLogService::log(
            action: AuditAction::UPDATE_USER,
            userId: $user->id,
            entityType: 'user',
            entityId: $user->id,
            newValues: ['avatar' => $filename],
        );

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data'    => [
                'avatar_url' => \Illuminate\Support\Facades\Storage::url($filename),
            ],
        ]);
    }
}