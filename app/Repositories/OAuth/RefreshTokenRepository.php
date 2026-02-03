<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use App\Entities\OAuth\RefreshTokenEntity;
use Illuminate\Support\Facades\Cache;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * Creates a new refresh token
     *
     * @return RefreshTokenEntityInterface
     */
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    /**
     * Create a new refresh token_name.
     *
     * @param RefreshTokenEntityInterface $refreshTokenEntity
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $ttl = $refreshTokenEntity->getExpiryDateTime()->getTimestamp() - time();
        
        Cache::put(
            'oauth_refresh_token:' . $refreshTokenEntity->getIdentifier(),
            [
                'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
                'expires_at' => $refreshTokenEntity->getExpiryDateTime()->getTimestamp(),
            ],
            $ttl
        );
    }

    /**
     * Revoke a refresh token.
     *
     * @param string $tokenId
     */
    public function revokeRefreshToken($tokenId)
    {
        Cache::forget('oauth_refresh_token:' . $tokenId);
    }

    /**
     * Check if the refresh token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        return !Cache::has('oauth_refresh_token:' . $tokenId);
    }
}
