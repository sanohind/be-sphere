<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use App\Entities\OAuth\AccessTokenEntity;
use Illuminate\Support\Facades\Cache;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * Create a new access token
     *
     * @param ClientEntityInterface  $clientEntity
     * @param ScopeEntityInterface[] $scopes
     * @param mixed                  $userIdentifier
     *
     * @return AccessTokenEntityInterface
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }

    /**
     * Persists a new access token to permanent storage.
     *
     * @param AccessTokenEntityInterface $accessTokenEntity
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $ttl = $accessTokenEntity->getExpiryDateTime()->getTimestamp() - time();
        
        Cache::put(
            'oauth_access_token:' . $accessTokenEntity->getIdentifier(),
            [
                'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
                'user_id' => $accessTokenEntity->getUserIdentifier(),
                'scopes' => $accessTokenEntity->getScopes(),
                'expires_at' => $accessTokenEntity->getExpiryDateTime()->getTimestamp(),
            ],
            $ttl
        );
    }

    /**
     * Revoke an access token.
     *
     * @param string $tokenId
     */
    public function revokeAccessToken($tokenId)
    {
        Cache::forget('oauth_access_token:' . $tokenId);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($tokenId)
    {
        return !Cache::has('oauth_access_token:' . $tokenId);
    }
}
