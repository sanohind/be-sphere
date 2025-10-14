<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data, User $creator): User
    {
        // Validate role
        $role = Role::findOrFail($data['role_id']);

        if (!$creator->canCreateUser($role)) {
            throw new \Exception('You do not have permission to create user with this role', 403);
        }

        // For admin, set department_id same as creator
        if ($creator->isAdmin()) {
            $data['department_id'] = $creator->department_id;
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = $creator->id;

        // Create user
        $user = User::create($data);

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
        $oldValues = $user->only(['email', 'username', 'name', 'role_id', 'department_id', 'is_active']);

        // Update password if provided
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

    public function getUsersByCreator(User $creator)
    {
        $query = User::with(['role', 'department', 'creator']);

        if ($creator->isSuperadmin()) {
            // Superadmin can see all users
            return $query->get();
        }

        if ($creator->isAdmin()) {
            // Admin can see users they created (operators in their department)
            return $query->where('created_by', $creator->id)->get();
        }

        // Operators and users cannot see other users
        return collect([]);
    }
}
