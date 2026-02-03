<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use App\Entities\OAuth\ScopeEntity;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * Return information about a scope.
     *
     * @param string $identifier The scope identifier
     *
     * @return ScopeEntityInterface|null
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        $scopes = config('oauth2.scopes');

        if (array_key_exists($identifier, $scopes)) {
            return new ScopeEntity($identifier);
        }

        return null;
    }

    /**
     * Given a client, grant type and optional user identifier validate the set of scopes requested are valid
     * and optionally append additional scopes or remove requested scopes.
     *
     * @param ScopeEntityInterface[] $scopes
     * @param string                 $grantType
     * @param ClientEntityInterface  $clientEntity
     * @param null|string            $userIdentifier
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        // Enforce default scopes if none requested
        if (empty($scopes)) {
            $defaultScopes = config('oauth2.default_scopes');
            foreach ($defaultScopes as $scopeId) {
                $scopes[] = new ScopeEntity($scopeId);
            }
        }
        
        return $scopes;
    }
}
