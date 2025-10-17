<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->boot();

try {
  // Create test user
  $user = \App\Models\User::factory()->create([
    'username' => 'jwt_test_user',
    'fullname' => 'JWT Test User'
  ]);

  echo "✅ User created: {$user->username}\n";

  // Test JWT login
  $credentials = ['username' => 'jwt_test_user', 'password' => 'password'];
  $token = auth('api')->attempt($credentials);

  if ($token) {
    echo "✅ JWT Token generated\n";

    $authUser = auth('api')->user();
    echo "✅ Authenticated user: {$authUser->username}\n";

    // Check response structure
    $response = [
      'access_token' => $token,
      'token_type' => 'bearer',
      'expires_in' => config('jwt.ttl') * 60,
      'user' => $authUser->toArray()
    ];

    echo "📋 Response structure:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
  } else {
    echo "❌ JWT authentication failed\n";
  }
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
}
