<?php

/**
 * Test Print Client Authentication
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔐 Testing Print Client Authentication\n";
echo "====================================\n\n";

$baseUrl = "http://karinox-fnb.local";
$apiKey = "karinox_print_client_2025";
$deviceId = "test_printer_001";

// Test cases
$tests = [
  [
    'name' => 'Get Device Status',
    'url' => '/api/pos/print-client/device/status',
    'method' => 'GET',
    'headers' => [
      'X-Print-Client-Key: ' . $apiKey,
      'X-Device-ID: ' . $deviceId
    ]
  ],
  [
    'name' => 'Get Print Queue',
    'url' => '/api/pos/print-client/queue?device_id=' . $deviceId,
    'method' => 'GET',
    'headers' => [
      'X-Print-Client-Key: ' . $apiKey,
      'X-Device-ID: ' . $deviceId
    ]
  ],
  [
    'name' => 'Invalid API Key',
    'url' => '/api/pos/print-client/device/status',
    'method' => 'GET',
    'headers' => [
      'X-Print-Client-Key: invalid_key',
      'X-Device-ID: ' . $deviceId
    ],
    'expect_error' => true
  ],
  [
    'name' => 'Missing API Key',
    'url' => '/api/pos/print-client/device/status',
    'method' => 'GET',
    'headers' => [
      'X-Device-ID: ' . $deviceId
    ],
    'expect_error' => true
  ],
  [
    'name' => 'Missing Device ID',
    'url' => '/api/pos/print-client/device/status',
    'method' => 'GET',
    'headers' => [
      'X-Print-Client-Key: ' . $apiKey
    ],
    'expect_error' => true
  ]
];

foreach ($tests as $test) {
  echo "🧪 Testing: {$test['name']}\n";

  $result = makeHttpRequest($baseUrl . $test['url'], $test['method'], $test['headers']);

  $expectError = $test['expect_error'] ?? false;

  if ($expectError) {
    if ($result['http_code'] >= 400) {
      echo "   ✅ Expected error received: {$result['http_code']}\n";
      if (isset($result['data']['message'])) {
        echo "   💬 Message: {$result['data']['message']}\n";
      }
    } else {
      echo "   ❌ Expected error but got success: {$result['http_code']}\n";
    }
  } else {
    if ($result['http_code'] == 200 && ($result['data']['success'] ?? false)) {
      echo "   ✅ Success: {$result['http_code']}\n";

      // Show specific data based on endpoint
      if (str_contains($test['url'], 'device/status')) {
        $data = $result['data']['data'] ?? [];
        echo "   📊 Device: {$data['device_id']}\n";
        echo "   📋 Pending jobs: {$data['queue_stats']['pending']}\n";
      } elseif (str_contains($test['url'], 'queue')) {
        $jobs = $result['data']['jobs'] ?? [];
        echo "   📋 Jobs returned: " . count($jobs) . "\n";
      }
    } else {
      echo "   ❌ Failed: {$result['http_code']}\n";
      if (isset($result['data']['message'])) {
        echo "   💬 Message: {$result['data']['message']}\n";
      }
    }
  }

  echo "\n";
  usleep(200000); // 0.2 second delay
}

// Test mark job processed if there are jobs
echo "🧪 Testing Job Processing\n";
$queueResult = makeHttpRequest($baseUrl . '/api/pos/print-client/queue?device_id=' . $deviceId, 'GET', [
  'X-Print-Client-Key: ' . $apiKey,
  'X-Device-ID: ' . $deviceId
]);

if ($queueResult['http_code'] == 200) {
  $jobs = $queueResult['data']['jobs'] ?? [];

  if (!empty($jobs)) {
    $job = $jobs[0];
    echo "   🖨️  Testing mark job #{$job['id']} as processed\n";

    $processResult = makeHttpRequest(
      $baseUrl . '/api/pos/print-client/queue/' . $job['id'] . '/processed',
      'POST',
      [
        'X-Print-Client-Key: ' . $apiKey,
        'X-Device-ID: ' . $deviceId,
        'Content-Type: application/json'
      ]
    );

    if ($processResult['http_code'] == 200 && ($processResult['data']['success'] ?? false)) {
      echo "   ✅ Job marked as processed successfully\n";
    } else {
      echo "   ❌ Failed to mark job as processed\n";
    }
  } else {
    echo "   📋 No jobs to test with\n";
  }
} else {
  echo "   ❌ Failed to get queue for testing\n";
}

echo "\n🎉 Print client authentication tests completed!\n";

function makeHttpRequest($url, $method = 'GET', $headers = [], $data = null)
{
  $ch = curl_init();

  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true
  ]);

  if ($data && ($method == 'POST' || $method == 'PUT')) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
  }

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);

  curl_close($ch);

  if ($error) {
    return [
      'http_code' => 0,
      'data' => ['error' => $error],
      'raw' => null
    ];
  }

  $decodedResponse = json_decode($response, true);

  return [
    'http_code' => $httpCode,
    'data' => $decodedResponse ?: [],
    'raw' => $response
  ];
}
