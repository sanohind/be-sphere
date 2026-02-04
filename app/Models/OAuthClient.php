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
        'client_id',
        'client_secret',
        'name',
        'redirect_uris',
        'scopes',
        'is_confidential',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_confidential' => 'boolean',
        'is_active' => 'boolean',
        'redirect_uris' => 'array',
        'scopes' => 'array',
    ];

    /**
     * Determine if the client is a confidential client.
     */
    public function confidential(): bool
    {
        return $this->is_confidential && !empty($this->client_secret);
    }

    /**
     * Get redirect URI (first one from array or single value)
     */
    public function getRedirectAttribute(): string
    {
        if (is_array($this->redirect_uris) && !empty($this->redirect_uris)) {
            return $this->redirect_uris[0];
        }
        return is_string($this->redirect_uris) ? $this->redirect_uris : '';
    }

    /**
     * Get secret (alias for client_secret for backward compatibility)
     */
    public function getSecretAttribute(): ?string
    {
        return $this->client_secret;
    }
}
