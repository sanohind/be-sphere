<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use App\Entities\OAuth\ClientEntity;
use App\Models\OAuthClient;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Get a client.
     *
     * @param string $clientIdentifier The client's identifier (can be numeric ID or client_id string)
     *
     * @return ClientEntityInterface|null
     */
    public function getClientEntity($clientIdentifier)
    {
        $client = null;

        // Try to find by numeric ID first (for backward compatibility)
        if (is_numeric($clientIdentifier)) {
            $client = OAuthClient::where('id', (int) $clientIdentifier)
                ->where('is_active', true)
                ->first();
        }

        // If not found by ID, try by client_id string
        if (!$client) {
            $client = OAuthClient::where('client_id', $clientIdentifier)
                ->where('is_active', true)
                ->first();
        }

        if (!$client) {
            return null;
        }

        // Get redirect URIs (handle both array and string)
        $redirectUris = [];
        if (is_array($client->redirect_uris) && !empty($client->redirect_uris)) {
            $redirectUris = $client->redirect_uris;
        } elseif (is_string($client->redirect_uris)) {
            // Try to decode JSON string
            $decoded = json_decode($client->redirect_uris, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $redirectUris = $decoded;
            } else {
                $redirectUris = [$client->redirect_uris];
            }
        } elseif (!empty($client->redirect)) {
            // Fallback to redirect attribute if available
            $redirectUris = [$client->redirect];
        }

        // If no redirect URIs found, use empty array (will be validated by OAuth server)
        if (empty($redirectUris)) {
            $redirectUris = [];
        }

        // Join redirect URIs with comma for ClientEntity (it will split them)
        $redirectUriString = implode(',', $redirectUris);

        // A client is confidential if it's marked as confidential and has a secret
        $isConfidential = $client->is_confidential && !empty($client->client_secret);

        // Use client_id as identifier if available, otherwise use numeric ID
        $identifier = $client->client_id ?? (string) $client->id;

        return new ClientEntity(
            $identifier,
            $client->name,
            $redirectUriString,
            $isConfidential
        );
    }

    /**
     * Validate a client's secret.
     *
     * @param string      $clientIdentifier The client's identifier (can be numeric ID or client_id string)
     * @param string|null $clientSecret     The client's secret (if sent)
     * @param string|null $grantType        The type of grant the client is using (if sent)
     *
     * @return bool
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $client = null;

        // Try to find by numeric ID first (for backward compatibility)
        if (is_numeric($clientIdentifier)) {
            $client = OAuthClient::where('id', (int) $clientIdentifier)
                ->where('is_active', true)
                ->first();
        }

        // If not found by ID, try by client_id string
        if (!$client) {
            $client = OAuthClient::where('client_id', $clientIdentifier)
                ->where('is_active', true)
                ->first();
        }

        if (!$client) {
            return false;
        }

        // If client is not confidential or has no secret, it's a public client (SPA)
        // Public clients don't require secret validation
        if (!$client->is_confidential || empty($client->client_secret)) {
            return true;
        }

        // For confidential clients, validate the secret
        if (empty($clientSecret)) {
            return false;
        }

        return hash_equals($client->client_secret, (string) $clientSecret);
    }
}
