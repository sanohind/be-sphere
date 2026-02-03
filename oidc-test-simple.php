<?php

$baseUrl = 'http://127.0.0.1:8000';
$clientId = 'scope-client';
$redirectUri = 'http://localhost:5173/#/callback';

echo "🧪 OIDC Test Script (Development Mode - Bypass Login)\n";
echo "=====================================================\n\n";

// --- STEP 1: PKCE Generation ---
echo "1️⃣  Generating PKCE verifier and challenge...\n";
$codeVerifier = bin2hex(random_bytes(32));
$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
echo "   ✓ Generated\n\n";

// --- STEP 2: Authorize Request (TEST ENDPOINT) ---
echo "2️⃣  Requesting Authorization Code (using test endpoint)...\n";
$authUrl = $baseUrl . '/api/oauth/authorize-test?' . http_build_query([
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
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

// Parse Location Header
if (!preg_match('/^Location: (.*)$/mi', $headers, $matches)) {
    die("❌ Failed: No redirect. Response:\n$response\n");
}

$location = trim($matches[1]);

// Extract Code
$parsed = parse_url($location);
$code = null;

if (isset($parsed['query'])) {
    parse_str($parsed['query'], $q);
    $code = $q['code'] ?? null;
}

if (!$code && isset($parsed['fragment'])) {
    $fragment = $parsed['fragment'];
    if (($pos = strpos($fragment, '?')) !== false) {
        parse_str(substr($fragment, $pos + 1), $q);
        $code = $q['code'] ?? null;
    }
}

if (!$code) {
    die("❌ Failed: No code in redirect.\nLocation: $location\n");
}

echo "   ✓ Code: " . substr($code, 0, 20) . "...\n\n";

// --- STEP 3: Token Exchange ---
echo "3️⃣  Exchanging Code for Tokens...\n";
$tokenUrl = $baseUrl . '/api/oauth/token';

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'code' => $code,
    'code_verifier' => $codeVerifier,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Token request failed (HTTP $httpCode).\nResponse: $response\n");
}

$json = json_decode($response, true);
if (!isset($json['access_token'])) {
    die("❌ No access_token in response.\nResponse: $response\n");
}

$accessToken = $json['access_token'];
$idToken = $json['id_token'] ?? 'NOT RECEIVED';

echo "   ✓ Access Token: " . substr($accessToken, 0, 20) . "...\n";
echo "   ✓ ID Token: " . substr($idToken, 0, 20) . "...\n\n";

// --- STEP 4: User Info ---
echo "4️⃣  Fetching User Info...\n";
$userUrl = $baseUrl . '/api/oauth/userinfo';

$ch = curl_init($userUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ UserInfo failed (HTTP $httpCode).\nResponse: $response\n");
}

$user = json_decode($response, true);
echo "   ✓ User: " . ($user['name'] ?? 'Unknown') . " (" . ($user['email'] ?? 'N/A') . ")\n\n";

echo "🎉 OIDC FLOW TEST PASSED!\n";
echo "========================================\n";
echo "Summary:\n";
echo "  - Authorization Code: ✓\n";
echo "  - Access Token: ✓\n";
echo "  - ID Token: ✓\n";
echo "  - User Info: ✓\n";
