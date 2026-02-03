<?php

$baseUrl = 'http://127.0.0.1:8000';
$clientId = 'scope-client';
$redirectUri = 'http://localhost:5173/#/callback';

echo "🚀 OIDC Authorization Flow Test Script\n";
echo "====================================\n\n";

// --- STEP 1: PKCE Generation ---
echo "1️⃣  Generating PKCE verifier and challenge...\n";
$codeVerifier = bin2hex(random_bytes(32));
$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
echo "   Verifier : " . substr($codeVerifier, 0, 10) . "...\n";
echo "   Challenge: " . substr($codeChallenge, 0, 10) . "...\n\n";

// --- STEP 2: Authorize Request ---
echo "2️⃣  Sending Authorization Request...\n";
// Note: We use the server endpoint directly. In a real app, the browser would visit this URL.
// Our controller auto-approves the request for testing purposes.
$authUrl = $baseUrl . '/api/oauth/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'scope' => 'openid profile email',
    'state' => 'test-state-' . time(),
    'code_challenge' => $codeChallenge,
    'code_challenge_method' => 'S256',
]);

$ch = curl_init($authUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);     // Need headers for Location
curl_setopt($ch, CURLOPT_NOBODY, false);    // Need body for debugging errors
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Manual redirect handling

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

// Parse Location Header
if (!preg_match('/^Location: (.*)$/mi', $headers, $matches)) {
    die("❌ Failed: No 'Location' header found. Server might have returned an error.\nHeaders:\n$headers\nBody:\n$body\n");
}

$location = trim($matches[1]);
echo "   Redirected to: " . substr($location, 0, 60) . "...\n";

// Extract Code (Handle Fragment /#/ callback which is common in Hash Routers)
$parsed = parse_url($location);
$code = null;

// Try Query param (Standard OAuth2)
if (isset($parsed['query'])) {
    parse_str($parsed['query'], $q);
    $code = $q['code'] ?? null;
}

// Try Fragment (If Access Token or Code is appended to fragment in SPA)
if (!$code && isset($parsed['fragment'])) {
    // Fragment might be "/callback?code=..."
    $fragment = $parsed['fragment'];
    // Look for ? inside fragment
    if (($pos = strpos($fragment, '?')) !== false) {
        $queryString = substr($fragment, $pos + 1);
        parse_str($queryString, $q);
        $code = $q['code'] ?? null;
    }
}

if (!$code) {
    die("❌ Failed: Could not extract authorization code from URL.\nLocation: $location\n");
}

echo "✅ Auth Code received: " . substr($code, 0, 20) . "...\n\n";

// --- STEP 3: Token Exchange ---
echo "3️⃣  Exchanging Code for Tokens...\n";
$tokenUrl = $baseUrl . '/api/oauth/token';

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'client_id' => $clientId,
    // 'client_secret' => ... (Public client with PKCE doesn't strictly need secret if not enforced)
    'redirect_uri' => $redirectUri,
    'code' => $code,
    'code_verifier' => $codeVerifier,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = json_decode($response, true);

if ($httpCode !== 200 || !isset($json['access_token'])) {
    die("❌ Failed to get token (HTTP $httpCode).\nResponse: $response\n");
}

$accessToken = $json['access_token'];
$idToken = $json['id_token'] ?? 'NOT RECEIVED';

echo "✅ Access Token: " . substr($accessToken, 0, 20) . "...\n";
echo "✅ ID Token    : " . substr($idToken, 0, 20) . "...\n\n";

// --- STEP 4: User Info ---
echo "4️⃣  Fetching User Info...\n";
$userUrl = $baseUrl . '/api/oauth/userinfo';

$ch = curl_init($userUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: $httpCode\n";
echo "   User Data  : $response\n";

if ($httpCode === 200) {
    echo "\n🎉 TEST COMPLETED SUCCESSFULLY!\n";
} else {
    echo "\n❌ User Info failed.\n";
}
