<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use App\Entities\OAuth\AuthCodeEntity;
use Illuminate\Support\Facades\Cache;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * Creates a new auth code
     *
     * @return AuthCodeEntityInterface
     */
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }

    /**
     * Persists a new auth code to permanent storage.
     *
     * @param AuthCodeEntityInterface $authCodeEntity
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $ttl = $authCodeEntity->getExpiryDateTime()->getTimestamp() - time();
        
        Cache::put(
            'oauth_auth_code:' . $authCodeEntity->getIdentifier(),
            [
                'client_id' => $authCodeEntity->getClient()->getIdentifier(),
                'user_id' => $authCodeEntity->getUserIdentifier(),
                'scopes' => $authCodeEntity->getScopes(),
                'expires_at' => $authCodeEntity->getExpiryDateTime()->getTimestamp(),
                'redirect_uri' => $authCodeEntity->getRedirectUri(),
            ],
            $ttl
        );
    }

    /**
     * Revoke an auth code.
     *
     * @param string $codeId
     */
    public function revokeAuthCode($codeId)
    {
        Cache::forget('oauth_auth_code:' . $codeId);
    }

    /**
     * Check if the auth code has been revoked.
     *
     * @param string $codeId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isAuthCodeRevoked($codeId)
    {
        return !Cache::has('oauth_auth_code:' . $codeId);
    }
}
