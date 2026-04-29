<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Mail\WelcomeNewUserMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function createUser(array $data, User $creator): User
    {
        // Validate role
        $role = Role::findOrFail($data['role_id']);

        if (!$creator->canCreateUser($role)) {
            throw new \Exception('You do not have permission to create user with this role', 403);
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = $creator->id;

        // Generate password setup token (valid 24 hours)
        $token = Str::random(64);
        $data['password_reset_token'] = $token;
        $data['password_reset_expires_at'] = now()->addHours(24);

        // Create user
        $user = User::create($data);

        // Send welcome email with set-password link
        try {
            Mail::to($user->email)->send(new WelcomeNewUserMail($user, $token));
        } catch (\Exception $e) {
            // Log email failure but don't block user creation
            \Log::error('Failed to send welcome email to ' . $user->email . ': ' . $e->getMessage());
        }

        // Log audit
        AuditLogService::log(
            action: AuditAction::CREATE_USER,
            userId: $creator->id,
            entityType: 'user',
            entityId: $user->id,
            newValues: $user->only(['email', 'username', 'name', 'role_id', 'department_id'])
        );

        return $user->load(['role', 'department']);
    }

    public function updateUser(User $user, array $data, User $updater): User
    {
        // Hanya Superadmin yang dapat mengupdate user
        if (!$updater->isSuperadmin()) {
            throw new \Exception('You do not have permission to update users', 403);
        }

        $oldValues = $user->only(['email', 'username', 'name', 'role_id', 'department_id', 'is_active']);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        // Log audit
        AuditLogService::log(
            action: (($data['is_active'] ?? $user->is_active) ? AuditAction::UPDATE_USER : AuditAction::DEACTIVATE_USER),
            userId: $updater->id,
            entityType: 'user',
            entityId: $user->id,
            oldValues: $oldValues,
            newValues: $user->only(['email', 'username', 'name', 'role_id', 'department_id', 'is_active'])
        );

        return $user->load(['role', 'department']);
    }

    public function deleteUser(User $user, User $deleter): bool
    {
        // Hanya Superadmin yang dapat menghapus user
        if (!$deleter->isSuperadmin()) {
            throw new \Exception('You do not have permission to delete users', 403);
        }

        if ($user->id === $deleter->id) {
            throw new \Exception('You cannot delete yourself', 403);
        }

        AuditLogService::log(
            action: AuditAction::DELETE_USER,
            userId: $deleter->id,
            entityType: 'user',
            entityId: $user->id,
            oldValues: $user->only(['email', 'username', 'name', 'role_id', 'department_id'])
        );

        return $user->delete();
    }

    public function getUsersByCreator(User $creator)
    {
        // Selalu exclude diri sendiri dari daftar
        $query = User::with(['role', 'department', 'creator'])
            ->where('id', '!=', $creator->id);

        // Hanya Superadmin yang dapat melihat semua user
        if ($creator->isSuperadmin()) {
            return $query->get();
        }

        return collect([]);
    }
}
