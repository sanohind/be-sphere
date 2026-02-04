<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Repositories\OAuth\ClientRepository;

$repository = new ClientRepository();

echo "Testing Client Lookup:\n";
echo str_repeat("=", 60) . "\n";

// Test 1: Lookup by numeric ID (what AMS sends)
echo "\n1. Testing lookup by numeric ID '2':\n";
$clientEntity = $repository->getClientEntity('2');
if ($clientEntity) {
    echo "   ✓ Found client: {$clientEntity->getName()}\n";
    echo "   Identifier: {$clientEntity->getIdentifier()}\n";
    echo "   Redirect URIs: " . implode(', ', $clientEntity->getRedirectUri()) . "\n";
    echo "   Is Confidential: " . ($clientEntity->isConfidential() ? 'Yes' : 'No') . "\n";
} else {
    echo "   ✗ Client not found\n";
}

// Test 2: Lookup by client_id string
echo "\n2. Testing lookup by client_id 'ams-client':\n";
$clientEntity = $repository->getClientEntity('ams-client');
if ($clientEntity) {
    echo "   ✓ Found client: {$clientEntity->getName()}\n";
    echo "   Identifier: {$clientEntity->getIdentifier()}\n";
} else {
    echo "   ✗ Client not found\n";
}

// Test 3: Validate client secret
echo "\n3. Testing client secret validation for ID '2':\n";
$amsSecret = 'ltJeOVUz7vbVm3KDwXrN1HMRtMoZGrofr7W5Bc2t';
$isValid = $repository->validateClient('2', $amsSecret, 'authorization_code');
echo "   Secret validation: " . ($isValid ? '✓ Valid' : '✗ Invalid') . "\n";

// Test 4: Validate with wrong secret
echo "\n4. Testing with wrong secret:\n";
$isValid = $repository->validateClient('2', 'wrong-secret', 'authorization_code');
echo "   Secret validation: " . ($isValid ? '✓ Valid (unexpected!)' : '✗ Invalid (expected)') . "\n";

// Test 5: Validate without secret (should fail for confidential client)
echo "\n5. Testing without secret (should fail for confidential client):\n";
$isValid = $repository->validateClient('2', null, 'authorization_code');
echo "   Secret validation: " . ($isValid ? '✓ Valid (unexpected!)' : '✗ Invalid (expected)') . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "Tests completed!\n";
