<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OAuthClient;
use Illuminate\Support\Str;

class OAuthClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates OAuth clients for SCOPE and AMS applications
     * Each client gets a unique ID and secret for authentication
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'SCOPE Application',
                'client_id' => 'scope-client',
                'redirect_uris' => [env('SCOPE_CALLBACK_URL', 'http://localhost:5175/#/callback')],
                'scopes' => ['openid', 'profile', 'email'],
                'is_confidential' => true,
                'is_active' => true,
            ],
            [
                'name' => 'AMS (Arrival Management System)',
                'client_id' => 'ams-client',
                'redirect_uris' => [env('AMS_CALLBACK_URL', 'http://localhost:5174/#/callback')],
                'scopes' => ['openid', 'profile', 'email'],
                'is_confidential' => true,
                'is_active' => true,
            ],
        ];

        foreach ($clients as $clientData) {
            // Check if client already exists by name or client_id
            $existingClient = OAuthClient::where('name', $clientData['name'])
                ->orWhere('client_id', $clientData['client_id'])
                ->first();

            if ($existingClient) {
                $this->command->info("Client '{$clientData['name']}' already exists. Skipping...");
                continue;
            }

            // Generate a secure random secret for confidential clients
            $clientSecret = Str::random(80);

            // Create client with new schema
            $client = OAuthClient::create(array_merge($clientData, [
                'client_secret' => $clientSecret,
            ]));

            $this->command->info("Created OAuth Client: {$client->name}");
            $this->command->info("  Client ID: {$client->client_id} (Database ID: {$client->id})");
            $this->command->info("  Client Secret: {$client->client_secret}");
            $this->command->info("  Redirect URIs: " . implode(', ', (array) $client->redirect_uris));
            $this->command->info("  Is Confidential: " . ($client->is_confidential ? 'Yes' : 'No'));
            $this->command->line('');

            // Save to .env file (for reference)
            $envKey = strtoupper(str_replace([' ', '(', ')'], ['_', '', ''], $client->name));
            $this->command->warn("Add these to your .env file:");
            $this->command->line("VITE_OIDC_CLIENT_ID={$client->id}  # or use client_id: {$client->client_id}");
            $this->command->line("VITE_OIDC_CLIENT_SECRET={$client->client_secret}");
            $this->command->line('');
        }

        $this->command->info('OAuth clients seeded successfully!');
        $this->command->warn('IMPORTANT: Save the client secrets above. They will not be shown again!');
    }
}
