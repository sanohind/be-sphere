<?php

// Generate RSA keys for OAuth2 JWT signing

$config = array(
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

// Generate private key
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privKey);

// Get public key
$pubKey = openssl_pkey_get_details($res);

// Save keys
file_put_contents(__DIR__ . '/storage/oauth/private.key', $privKey);
file_put_contents(__DIR__ . '/storage/oauth/public.key', $pubKey['key']);

// Set permissions
chmod(__DIR__ . '/storage/oauth/private.key', 0600);
chmod(__DIR__ . '/storage/oauth/public.key', 0644);

echo "✅ RSA keys generated successfully!\n";
echo "Private key: storage/oauth/private.key\n";
echo "Public key: storage/oauth/public.key\n";
