<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthClient extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'oauth_clients';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'secret',
        'provider',
        'redirect',
        'personal_access_client',
        'password_client',
        'revoked',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'personal_access_client' => 'boolean',
        'password_client' => 'boolean',
        'revoked' => 'boolean',
    ];

    /**
     * Determine if the client is a confidential client.
     */
    public function confidential(): bool
    {
        return !empty($this->secret);
    }

    /**
     * Get the user that owns the client.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
