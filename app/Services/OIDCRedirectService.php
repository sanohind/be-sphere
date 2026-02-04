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
    public function generateAuthorizationUrl(string $projectId, string $clientName, string $redirectUri): string
    {
        // Get the OAuth client from database
        $client = OAuthClient::where('name', $clientName)->first();

        if (!$client) {
            throw new \Exception("OAuth client '{$clientName}' not found");
        }

        // Generate a secure random state parameter for CSRF protection
        $state = Str::random(40);

        // Build authorization URL with required OAuth parameters
        $params = http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

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
                'redirect_uri' => env('SCOPE_CALLBACK_URL', 'http://localhost:5175/#/callback'),
            ],
            'ams' => [
                'client_name' => 'AMS (Arrival Management System)',
                'redirect_uri' => env('AMS_CALLBACK_URL', 'http://localhost:5174/#/callback'),
            ],
        ];

        return $configs[$projectId] ?? null;
    }
}
