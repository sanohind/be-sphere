<?php

namespace App\Services;

use App\Models\OAuthClient;
use Illuminate\Support\Str;

class OIDCRedirectService
{
    /**
     * Generate OIDC authorization URL for a given project
     *
     * @param string $projectId The project identifier (e.g., 'scope', 'ams')
     * @param string $clientName The registered OAuth client name
     * @param string $redirectUri The callback URI for the client application
     * @return string The authorization URL
     */
    public function generateAuthorizationUrl(string $projectId, string $clientName, string $redirectUri, ?string $token = null): string
    {
        // Get the OAuth client from database
        $client = OAuthClient::where('name', $clientName)->first();

        if (!$client) {
            throw new \Exception("OAuth client '{$clientName}' not found");
        }

        // Generate a secure random state parameter for CSRF protection
        $state = Str::random(40);

        // Build parameters array
        $queryParams = [
            'client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ];

        // Append token if provided (for SSO handshake)
        if ($token) {
            $queryParams['token'] = $token;
        }

        // Build authorization URL with required OAuth parameters
        $params = http_build_query($queryParams);

        $baseUrl = config('app.url');

        return "{$baseUrl}/api/oauth/authorize?{$params}";
    }

    /**
     * Get client configuration for a project
     *
     * @param string $projectId
     * @return array|null
     */
    public function getClientConfig(string $projectId): ?array
    {
        $configs = [
            'scope' => [
                'client_name' => 'SCOPE Application',
            ],
            'ams' => [
                'client_name' => 'AMS (Arrival Management System)',
            ],
            'cch' => [
                'client_name' => 'CCH Application',
            ],
        ];

        if (!isset($configs[$projectId])) {
            return null;
        }

        $config = $configs[$projectId];
        
        // Get client from database to get actual redirect URI
        $client = OAuthClient::where('name', $config['client_name'])->first();
        
        if (!$client) {
            return null;
        }

        // Get redirect URI from database (first one from array)
        $redirectUri = '';
        if (is_array($client->redirect_uris) && !empty($client->redirect_uris)) {
            $redirectUri = $client->redirect_uris[0];
        } elseif (is_string($client->redirect_uris)) {
            // Try to decode JSON string
            $decoded = json_decode($client->redirect_uris, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                $redirectUri = $decoded[0];
            } else {
                $redirectUri = $client->redirect_uris;
            }
        }

        // Fallback to env if database doesn't have redirect URI
        if (empty($redirectUri)) {
            if ($projectId === 'scope') {
                $redirectUri = env('SCOPE_CALLBACK_URL', 'http://localhost:5175/#/callback');
            } elseif ($projectId === 'ams') {
                $redirectUri = env('AMS_CALLBACK_URL', 'http://localhost:5174/#/callback');
            } else {
                $redirectUri = env('CCH_CALLBACK_URL', 'http://localhost:5176/#/sso/callback');
            }
        }

        $config['redirect_uri'] = $redirectUri;
        $config['client_id'] = $client->id; // Use numeric ID for compatibility

        return $config;
    }
}
