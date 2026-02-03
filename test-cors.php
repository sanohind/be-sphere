<?php

echo "🧪 Testing CORS Configuration\n";
echo "==============================\n\n";

$url = 'http://127.0.0.1:8000/api/auth/login';
$origin = 'http://localhost:5174';

// Test 1: OPTIONS (Preflight) Request
echo "1️⃣  Testing CORS Preflight (OPTIONS)...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Origin: ' . $origin,
    'Access-Control-Request-Method: POST',
    'Access-Control-Request-Headers: Content-Type, Authorization'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";

// Check for CORS headers
if (stripos($response, 'Access-Control-Allow-Origin') !== false) {
    echo "   ✓ Access-Control-Allow-Origin: Found\n";
    
    // Extract the header value
    if (preg_match('/Access-Control-Allow-Origin:\s*(.+)/i', $response, $matches)) {
        echo "   ✓ Value: " . trim($matches[1]) . "\n";
    }
} else {
    echo "   ❌ Access-Control-Allow-Origin: NOT FOUND\n";
}

if (stripos($response, 'Access-Control-Allow-Methods') !== false) {
    echo "   ✓ Access-Control-Allow-Methods: Found\n";
} else {
    echo "   ❌ Access-Control-Allow-Methods: NOT FOUND\n";
}

if (stripos($response, 'Access-Control-Allow-Headers') !== false) {
    echo "   ✓ Access-Control-Allow-Headers: Found\n";
} else {
    echo "   ❌ Access-Control-Allow-Headers: NOT FOUND\n";
}

echo "\n";

// Test 2: Actual POST Request
echo "2️⃣  Testing Actual POST Request with CORS...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'superadmin@besphere.com',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Origin: ' . $origin,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";

if (stripos($response, 'Access-Control-Allow-Origin') !== false) {
    echo "   ✓ CORS headers present in response\n";
} else {
    echo "   ❌ CORS headers missing in response\n";
}

if ($httpCode === 200) {
    echo "   ✓ Login successful\n";
} else {
    echo "   ⚠️  Login failed (check credentials or server)\n";
}

echo "\n✅ CORS Test Complete!\n";
