<?php

echo "🧪 Testing Backend Token Validation\n";
echo "====================================\n\n";

$sphereUrl = 'http://127.0.0.1:8000';
$scopeUrl = 'http://127.0.0.1:8000'; // SCOPE backend (same port for now)
$amsUrl = 'http://localhost:8002'; // AMS backend

// Step 1: Login to Sphere
echo "1️⃣  Logging in to Sphere...\n";
$ch = curl_init($sphereUrl . '/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'superadmin@besphere.com',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Login failed (HTTP $httpCode)\nResponse: $response\n");
}

$data = json_decode($response, true);
$token = null;

// Try different response structures
if (isset($data['access_token'])) {
    $token = $data['access_token'];
} elseif (isset($data['data']['access_token'])) {
    $token = $data['data']['access_token'];
}

if (!$token) {
    die("❌ No access token in response\nResponse: $response\n");
}

echo "   ✓ Token received: " . substr($token, 0, 30) . "...\n\n";

// Step 2: Test SCOPE Backend
echo "2️⃣  Testing SCOPE Backend (/api/auth/user)...\n";
$ch = curl_init($scopeUrl . '/api/auth/user');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "   ❌ SCOPE validation failed (HTTP $httpCode)\n";
    echo "   Response: $response\n\n";
} else {
    $userData = json_decode($response, true);
    if (isset($userData['success']) && $userData['success']) {
        echo "   ✓ SCOPE: Token validated successfully\n";
        echo "   ✓ User: " . ($userData['user']['name'] ?? 'Unknown') . "\n";
        echo "   ✓ Email: " . ($userData['user']['email'] ?? 'Unknown') . "\n\n";
    } else {
        echo "   ❌ SCOPE: Unexpected response\n";
        echo "   Response: $response\n\n";
    }
}

// Step 3: Test AMS Backend
echo "3️⃣  Testing AMS Backend (/api/auth/user)...\n";
$ch = curl_init($amsUrl . '/api/auth/user');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "   ⚠️  AMS: Connection failed - " . $curlError . "\n";
    echo "   (AMS backend might not be running on port 8002)\n\n";
} elseif ($httpCode !== 200) {
    echo "   ❌ AMS validation failed (HTTP $httpCode)\n";
    echo "   Response: $response\n\n";
} else {
    $userData = json_decode($response, true);
    if (isset($userData['success']) && $userData['success']) {
        echo "   ✓ AMS: Token validated successfully\n";
        echo "   ✓ User: " . ($userData['data']['user']['name'] ?? 'Unknown') . "\n";
        echo "   ✓ Email: " . ($userData['data']['user']['email'] ?? 'Unknown') . "\n\n";
    } else {
        echo "   ❌ AMS: Unexpected response\n";
        echo "   Response: $response\n\n";
    }
}

echo "✅ Test Complete!\n";
echo "==================\n";
echo "Summary:\n";
echo "  - Sphere Login: ✓\n";
echo "  - SCOPE Token Validation: Check output above\n";
echo "  - AMS Token Validation: Check output above\n";
