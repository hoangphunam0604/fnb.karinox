<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Http\Request;

// Load Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

/**
 * Test Print System API
 */
function testPrintSystem()
{
  $baseUrl = "http://karinox-fnb.local/api/pos";

  // Get first order to test with
  $orders = \App\Models\Order::with(['items', 'branch'])->limit(1)->get();

  if ($orders->isEmpty()) {
    echo "❌ No orders found. Please create some orders first.\n";
    return;
  }

  $order = $orders->first();
  echo "🔍 Testing with Order: {$order->order_code} (ID: {$order->id})\n";
  echo "   Items count: {$order->items->count()}\n";
  echo "   Branch: {$order->branch->name}\n\n";

  // Test cases
  $tests = [
    [
      'name' => 'Print Provisional Bill',
      'endpoint' => '/print/provisional',
      'data' => ['order_id' => $order->id, 'device_id' => 'test_printer_001']
    ],
    [
      'name' => 'Print Labels',
      'endpoint' => '/print/labels',
      'data' => ['order_id' => $order->id, 'device_id' => 'label_printer_001']
    ],
    [
      'name' => 'Print Kitchen Tickets',
      'endpoint' => '/print/kitchen',
      'data' => ['order_id' => $order->id, 'device_id' => 'kitchen_printer_001']
    ],
    [
      'name' => 'Auto Print',
      'endpoint' => '/print/auto',
      'data' => ['order_id' => $order->id, 'device_id' => 'pos_printer_001']
    ],
    [
      'name' => 'Get Print Queue',
      'endpoint' => '/print/queue',
      'method' => 'GET',
      'query' => 'device_id=test_printer_001&limit=5'
    ],
    [
      'name' => 'Get Order Print Status',
      'endpoint' => "/print/order/{$order->id}/status",
      'method' => 'GET'
    ],
    [
      'name' => 'Preview Invoice',
      'endpoint' => '/print/preview',
      'method' => 'GET',
      'query' => "order_id={$order->id}&type=invoice"
    ]
  ];

  $results = [];

  foreach ($tests as $test) {
    echo "🧪 Testing: {$test['name']}\n";

    try {
      $result = makeApiCall($baseUrl . $test['endpoint'], $test);
      $results[] = [
        'test' => $test['name'],
        'success' => $result['success'] ?? false,
        'response' => $result
      ];

      if ($result['success'] ?? false) {
        echo "   ✅ Success\n";
        if (isset($result['content']) && strlen($result['content']) > 100) {
          echo "   📄 Content length: " . strlen($result['content']) . " chars\n";
        }
        if (isset($result['print_job_id'])) {
          echo "   🆔 Print Job ID: {$result['print_job_id']}\n";
        }
        if (isset($result['jobs']) && is_array($result['jobs'])) {
          echo "   📋 Queue jobs: " . count($result['jobs']) . "\n";
        }
      } else {
        echo "   ❌ Failed: " . ($result['message'] ?? 'Unknown error') . "\n";
      }
    } catch (Exception $e) {
      echo "   💥 Exception: " . $e->getMessage() . "\n";
      $results[] = [
        'test' => $test['name'],
        'success' => false,
        'error' => $e->getMessage()
      ];
    }

    echo "\n";
    usleep(500000); // Wait 0.5 seconds between tests
  }

  // Summary
  echo "📊 Test Summary:\n";
  echo "================\n";

  $passed = 0;
  $failed = 0;

  foreach ($results as $result) {
    $status = $result['success'] ? '✅' : '❌';
    echo "{$status} {$result['test']}\n";

    if ($result['success']) {
      $passed++;
    } else {
      $failed++;
    }
  }

  echo "\n📈 Results: {$passed} passed, {$failed} failed\n";

  // Test print queue processing
  echo "\n🖨️  Testing Print Queue Processing:\n";
  testPrintQueueProcessing();
}

/**
 * Make API call (simulated)
 */
function makeApiCall($url, $test)
{
  // Since we can't make HTTP calls easily in this context,
  // we'll call the controller methods directly

  $controller = new \App\Http\Controllers\Api\PrintController(new \App\Services\PrintService());
  $request = new Request();

  // Set up request data
  if (isset($test['data'])) {
    $request->merge($test['data']);
  }

  if (isset($test['query'])) {
    parse_str($test['query'], $queryParams);
    $request->merge($queryParams);
  }

  try {
    $method = $test['method'] ?? 'POST';

    switch (true) {
      case str_contains($url, '/print/provisional'):
        $response = $controller->provisional($request);
        break;

      case str_contains($url, '/print/labels'):
        $response = $controller->labels($request);
        break;

      case str_contains($url, '/print/kitchen'):
        $response = $controller->kitchen($request);
        break;

      case str_contains($url, '/print/auto'):
        $response = $controller->autoPrint($request);
        break;

      case str_contains($url, '/print/queue'):
        $response = $controller->getQueue($request);
        break;

      case str_contains($url, '/print/order/') && str_contains($url, '/status'):
        $orderId = $request->get('order_id') ??
          preg_replace('/.*\/order\/(\d+)\/status.*/', '$1', $url);
        $order = \App\Models\Order::findOrFail($orderId);
        $response = $controller->getOrderPrintStatus($order);
        break;

      case str_contains($url, '/print/preview'):
        $response = $controller->preview($request);
        break;

      default:
        throw new Exception("Unknown endpoint: $url");
    }

    return json_decode($response->getContent(), true);
  } catch (Exception $e) {
    return [
      'success' => false,
      'message' => $e->getMessage()
    ];
  }
}

/**
 * Test print queue processing
 */
function testPrintQueueProcessing()
{
  // Get pending print jobs
  $pendingJobs = \App\Models\PrintQueue::pending()->limit(5)->get();

  echo "📋 Pending jobs: {$pendingJobs->count()}\n";

  if ($pendingJobs->isEmpty()) {
    echo "   No pending jobs to process.\n";
    return;
  }

  foreach ($pendingJobs as $job) {
    echo "🖨️  Processing job #{$job->id} ({$job->type})\n";

    try {
      // Simulate processing
      $job->update(['status' => 'processing']);

      // Simulate processing time
      usleep(100000); // 0.1 seconds

      // Mark as processed
      $job->markAsProcessed();
      echo "   ✅ Job #{$job->id} processed successfully\n";
    } catch (Exception $e) {
      $job->markAsFailed($e->getMessage());
      echo "   ❌ Job #{$job->id} failed: " . $e->getMessage() . "\n";
    }
  }
}

// Set up order items with print flags for testing
function setupTestData()
{
  echo "🔧 Setting up test data...\n";

  // Update some order items to have print flags
  $items = \App\Models\OrderItem::with('product')->limit(10)->get();

  foreach ($items as $item) {
    $printLabel = in_array($item->product->type, ['goods', 'processed', 'combo']);
    $printKitchen = in_array($item->product->type, ['processed', 'combo']);

    $item->update([
      'print_label' => $printLabel,
      'print_kitchen' => $printKitchen,
      'printed_label' => false,
      'printed_kitchen' => false
    ]);
  }

  echo "   ✅ Updated {$items->count()} order items with print flags\n\n";
}

// Run tests
try {
  echo "🚀 Starting Print System Tests\n";
  echo "==============================\n\n";

  setupTestData();
  testPrintSystem();

  echo "\n🎉 Print system tests completed!\n";
} catch (Exception $e) {
  echo "💥 Test failed: " . $e->getMessage() . "\n";
  echo "Trace: " . $e->getTraceAsString() . "\n";
}
