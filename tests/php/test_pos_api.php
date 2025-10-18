<?php

// Test POS API thực tế
require_once __DIR__ . '/../../vendor/autoload.php';

$baseUrl = 'http://localhost/karinox-fnb/public';
$url = $baseUrl . '/api/pos/products';

// Tạo JWT token test
$token = base64_encode(json_encode([
  'user_id' => 1,
  'branch_id' => 1,
  'exp' => time() + 3600
]));

$headers = [
  'Authorization: Bearer ' . $token,
  'karinox-app-id: karinox-app-pos',
  'X-Karinox-Branch-Id: 1',
  'Content-Type: application/json',
  'Accept: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

echo "🔗 Testing POS Products API: {$url}\n";
echo "📋 Headers:\n";
foreach ($headers as $header) {
  echo "   {$header}\n";
}
echo "\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Response Status: {$httpCode}\n";

if ($error) {
  echo "❌ cURL Error: {$error}\n";
  exit(1);
}

if ($response) {
  echo "📦 Response Body:\n";
  $data = json_decode($response, true);
  if ($data) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  } else {
    echo $response;
  }
  echo "\n";
} else {
  echo "❌ Empty response\n";
}

if ($httpCode === 200) {
  echo "✅ API test successful!\n";
} else {
  echo "❌ API test failed with status {$httpCode}\n";
}
