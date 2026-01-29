<?php
// ======================
// 1. Lấy mã khách hàng
// ======================
$customerCode = $_GET['code'] ?? null;

if (!$customerCode) {
  http_response_code(404);
  exit('Customer code not found');
}

// ======================
// 2. Gọi API
// ======================
$apiUrl = "http://karinox-fnb.nam/api/customers/{$customerCode}";

$payload = json_encode([]);


$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_TIMEOUT => 10,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Accept: application/json',
    // 'Authorization: Bearer YOUR_TOKEN', // nếu có
    'Content-Length: ' . strlen($payload),
  ],
]);


$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
  http_response_code(404);
  exit('Customer not found');
}
$response = trim($response);
$response = preg_replace('/^\xEF\xBB\xBF/', '', $response);

$json = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  var_dump(json_last_error_msg());
  exit;
}
$customer = $json['data'] ?? null;

if (!$customer) {
  exit('Customer data empty');
}
// ======================
// 3. Map dữ liệu từ API
// ======================
$avatar         = $customer['avatar'] ?? '';
$fullname       = $customer['fullname'] ?? '';
$phone          = $customer['phone'] ?? '';
$membershipName = $customer['membership_level']['name'] ?? '';
$arenaMember    = $customer['arena_member'] ?? '';
$arenaMemberExp = $customer['arena_member_exp'] ?? '';
