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
        // Check if updater has permission to update this user
        if ($updater->isAdmin()) {
            // Admin can only update users in their department
            if (!$updater->department_id || $user->department_id !== $updater->department_id) {
                throw new \Exception('You do not have permission to update this user', 403);
            }
            
            // Admin cannot change department_id or role_id
            unset($data['department_id']);
            unset($data['role_id']);
        } elseif (!$updater->isSuperadmin()) {
            // Only superadmin and admin can update users
            throw new \Exception('You do not have permission to update users', 403);
        }

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

    public function deleteUser(User $user, User $deleter): bool
    {
        // Check if deleter has permission to delete this user
        if ($deleter->isAdmin()) {
            // Admin can only delete users in their department
            if (!$deleter->department_id || $user->department_id !== $deleter->department_id) {
                throw new \Exception('You do not have permission to delete this user', 403);
            }
        } elseif (!$deleter->isSuperadmin()) {
            // Only superadmin and admin can delete users
            throw new \Exception('You do not have permission to delete users', 403);
        }

        // Prevent deleting yourself
        if ($user->id === $deleter->id) {
            throw new \Exception('You cannot delete yourself', 403);
        }

        // Log audit before deletion
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
        $query = User::with(['role', 'department', 'creator']);

        if ($creator->isSuperadmin()) {
            // Superadmin can see all users
            return $query->get();
        }

        if ($creator->isAdmin()) {
            // Admin can see all users in their department
            if ($creator->department_id) {
                return $query->where('department_id', $creator->department_id)->get();
            }
            // If admin has no department, return empty
            return collect([]);
        }

        // Operators and users cannot see other users
        return collect([]);
    }
}
