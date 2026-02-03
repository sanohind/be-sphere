<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
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
                'redirect' => env('SCOPE_CALLBACK_URL', 'http://localhost:5175/#/callback'),
                'personal_access_client' => false,
                'password_client' => false,
                'revoked' => false,
                'provider' => null,
            ],
            [
                'name' => 'AMS (Arrival Management System)',
                'redirect' => env('AMS_CALLBACK_URL', 'http://localhost:5174/#/callback'),
                'personal_access_client' => false,
                'password_client' => false,
                'revoked' => false,
                'provider' => null,
            ],
        ];

        foreach ($clients as $index => $clientData) {
            // Check if client already exists
            $existingClient = Client::where('name', $clientData['name'])->first();
            
            if ($existingClient) {
                $this->command->info("Client '{$clientData['name']}' already exists. Skipping...");
                continue;
            }
            
            // Create client
            $client = Client::create(array_merge($clientData, [
                'secret' => Str::random(40),
                'user_id' => null, // First-party client
            ]));
            
            $this->command->info("Created OAuth Client: {$client->name}");
            $this->command->info("  Client ID: {$client->id}");
            $this->command->info("  Client Secret: {$client->secret}");
            $this->command->info("  Redirect URI: {$client->redirect}");
            $this->command->line('');
            
            // Save to .env file (for reference)
            $envKey = strtoupper(str_replace([' ', '(', ')'], ['_', '', ''], $client->name));
            $this->command->warn("Add these to your .env file:");
            $this->command->line("{$envKey}_CLIENT_ID={$client->id}");
            $this->command->line("{$envKey}_CLIENT_SECRET={$client->secret}");
            $this->command->line('');
        }
        
        $this->command->info('OAuth clients seeded successfully!');
        $this->command->warn('IMPORTANT: Save the client secrets above. They will not be shown again!');
    }
}
