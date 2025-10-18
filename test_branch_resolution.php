<?php

// Test script để kiểm tra branch resolution
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;

// Bootstrap Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\InventoryService;

echo "=== TEST BRANCH ID RESOLUTION ===\n\n";

$inventoryService = new InventoryService();

// Test cases
$testCases = [
  // Test 1: Request input có giá trị
  [
    'description' => 'Request input có branch_id = 5',
    'request_input' => 5,
    'headers' => ['X-Branch-Id' => 10],
    'app_binding' => 15,
    'expected' => 5
  ],

  // Test 2: Không có request input, có header X-Branch-Id
  [
    'description' => 'Không có request input, có header X-Branch-Id = 10',
    'request_input' => null,
    'headers' => ['X-Branch-Id' => 10],
    'app_binding' => 15,
    'expected' => 10
  ],

  // Test 3: Không có request input và header, có app binding
  [
    'description' => 'Chỉ có app binding = 15',
    'request_input' => null,
    'headers' => [],
    'app_binding' => 15,
    'expected' => 15
  ],

  // Test 4: Không có gì cả
  [
    'description' => 'Không có source nào',
    'request_input' => null,
    'headers' => [],
    'app_binding' => null,
    'expected' => null
  ],

  // Test 5: Header Karinox-Branch-Id
  [
    'description' => 'Header Karinox-Branch-Id = 20',
    'request_input' => null,
    'headers' => ['Karinox-Branch-Id' => 20],
    'app_binding' => null,
    'expected' => 20
  ]
];

foreach ($testCases as $index => $testCase) {
  echo "Test " . ($index + 1) . ": " . $testCase['description'] . "\n";

  // Tạo mock request với headers
  $request = Request::create('/test', 'GET');
  foreach ($testCase['headers'] as $key => $value) {
    $request->headers->set($key, $value);
  }

  // Set app binding nếu có
  if ($testCase['app_binding']) {
    app()->instance('karinox_branch_id', $testCase['app_binding']);
  } else {
    // Clear app binding
    if (app()->bound('karinox_branch_id')) {
      app()->forgetInstance('karinox_branch_id');
    }
  }

  // Test resolution
  $result = $inventoryService->resolveBranchId($testCase['request_input'], $request);

  // Verify result
  if ($result == $testCase['expected']) {
    echo "  ✅ PASS - Got: " . var_export($result, true) . "\n";
  } else {
    echo "  ❌ FAIL - Expected: " . var_export($testCase['expected'], true) . ", Got: " . var_export($result, true) . "\n";
  }

  echo "\n";
}

echo "=== PRIORITY TEST ===\n";

// Test priority: request input > header > app binding
$request = Request::create('/test', 'GET');
$request->headers->set('X-Branch-Id', 100);
$request->headers->set('Karinox-Branch-Id', 200);
app()->instance('karinox_branch_id', 300);

$result = $inventoryService->resolveBranchId(999, $request);
echo "All sources present - Request input should win: ";
if ($result == 999) {
  echo "✅ PASS - Got 999\n";
} else {
  echo "❌ FAIL - Expected 999, Got $result\n";
}

$result = $inventoryService->resolveBranchId(null, $request);
echo "No request input - X-Branch-Id header should win: ";
if ($result == 100) {
  echo "✅ PASS - Got 100\n";
} else {
  echo "❌ FAIL - Expected 100, Got $result\n";
}

echo "\n✅ Branch Resolution Test completed!\n";
