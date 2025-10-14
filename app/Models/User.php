<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'username',
        'password',
        'name',
        'nik',
        'phone_number',
        'avatar',
        'role_id',
        'department_id',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'username' => $this->username,
            'role' => $this->role->slug,
            'role_level' => $this->role->level,
            'department_id' => $this->department_id,
        ];
    }

    // Relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Helper Methods
    public function isSuperadmin(): bool
    {
        return $this->role->level === 1;
    }

    public function isAdmin(): bool
    {
        return $this->role->level === 2;
    }

    public function isOperator(): bool
    {
        return $this->role->level === 3;
    }

    public function canCreateUser(Role $roleToCreate): bool
    {
        // Superadmin bisa buat semua role
        if ($this->isSuperadmin()) {
            return true;
        }

        // Admin hanya bisa buat operator di departmentnya
        if ($this->isAdmin()) {
            return $roleToCreate->level === 3 
                && $roleToCreate->department_id === $this->department_id;
        }

        return false;
    }
}
