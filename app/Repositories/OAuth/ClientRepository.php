<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use App\Entities\OAuth\ClientEntity;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Get a client.
     *
     * @param string $clientIdentifier The client's identifier
     *
     * @return ClientEntityInterface|null
     */
    public function getClientEntity($clientIdentifier)
    {
        $clients = config('oauth2.clients');

        foreach ($clients as $client) {
            if ($client['id'] === $clientIdentifier) {
                return new ClientEntity(
                    $client['id'],
                    $client['name'],
                    $client['redirect'],
                    $client['is_confidential']
                );
            }
        }

        return null;
    }

    /**
     * Validate a client's secret.
     *
     * @param string      $clientIdentifier The client's identifier
     * @param string|null $clientSecret     The client's secret (if sent)
     * @param string|null $grantType        The type of grant the client is using (if sent)
     *
     * @return bool
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $clients = config('oauth2.clients');

        foreach ($clients as $client) {
            if ($client['id'] === $clientIdentifier) {
                if ($client['is_confidential'] === false) {
                    return true;
                }
                
                return hash_equals($client['secret'], (string) $clientSecret);
            }
        }

        return false;
    }
}
