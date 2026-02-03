<?php

echo "🧪 Testing OIDC Discovery Endpoint\n\n";

$url = 'http://127.0.0.1:8000/api/.well-known/openid-configuration';

echo "URL: $url\n";
echo "Testing...\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ Error: $error\n";
    exit(1);
}

if ($httpCode === 200) {
    echo "✅ Success!\n\n";
    echo "Response:\n";
    $json = json_decode($response, true);
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n";
} else {
    echo "❌ Failed with HTTP $httpCode\n";
    echo "Response: $response\n";
}
