<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'level',
        'department_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Helper methods
    public function isSuperadmin(): bool
    {
        return $this->level === 1;
    }

    public function isAdmin(): bool
    {
        return $this->level === 2;
    }

    public function isOperator(): bool
    {
        return $this->level === 3;
    }

    public function isUser(): bool
    {
        return $this->level === 4;
    }
}