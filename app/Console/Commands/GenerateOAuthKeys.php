<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateOAuthKeys extends Command
{
    protected $signature = 'oauth:keys';
    protected $description = 'Generate RSA keys for OAuth2 JWT signing';

    public function handle()
    {
        $storagePath = storage_path('oauth');
        
        // Ensure directory exists
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $privateKeyPath = $storagePath . '/private.key';
        $publicKeyPath = $storagePath . '/public.key';

        // Generate private key
        $config = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        
        if ($res === false) {
            $this->error('Failed to generate private key');
            return 1;
        }

        openssl_pkey_export($res, $privKey);

        // Get public key
        $pubKeyDetails = openssl_pkey_get_details($res);
        $pubKey = $pubKeyDetails['key'];

        // Save keys
        file_put_contents($privateKeyPath, $privKey);
        file_put_contents($publicKeyPath, $pubKey);

        // Set permissions (Unix-like systems)
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($privateKeyPath, 0600);
            chmod($publicKeyPath, 0644);
        }

        $this->info('✅ RSA keys generated successfully!');
        $this->line('Private key: ' . $privateKeyPath);
        $this->line('Public key: ' . $publicKeyPath);

        return 0;
    }
}
