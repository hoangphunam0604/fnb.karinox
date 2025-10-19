<?php

/**
 * API Integration Test for Print System
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🌐 API Integration Test for Print System\n";
echo "=======================================\n\n";

// Test API endpoints
testPrintApis();

function testPrintApis()
{
  // Lấy order test
  $order = \App\Models\Order::with('items')->first();

  if (!$order) {
    echo "❌ No order found for testing\n";
    return;
  }

  echo "🎯 Testing with Order ID: {$order->id}\n\n";

  // Simulate API calls using controller directly
  $controller = new \App\Http\Controllers\Api\PrintController(new \App\Services\PrintService());

  $tests = [
    [
      'name' => 'POST /api/pos/print/provisional',
      'method' => 'provisional',
      'data' => ['order_id' => $order->id, 'device_id' => 'api_test_001']
    ],
    [
      'name' => 'POST /api/pos/print/labels',
      'method' => 'labels',
      'data' => ['order_id' => $order->id, 'device_id' => 'api_label_001']
    ],
    [
      'name' => 'POST /api/pos/print/kitchen',
      'method' => 'kitchen',
      'data' => ['order_id' => $order->id, 'device_id' => 'api_kitchen_001']
    ],
    [
      'name' => 'POST /api/pos/print/auto',
      'method' => 'autoPrint',
      'data' => ['order_id' => $order->id, 'device_id' => 'api_auto_001']
    ],
    [
      'name' => 'GET /api/pos/print/queue',
      'method' => 'getQueue',
      'data' => ['device_id' => 'api_test_001', 'limit' => 5]
    ],
    [
      'name' => 'GET /api/pos/print/order/{id}/status',
      'method' => 'getOrderPrintStatus',
      'data' => ['order' => $order]
    ]
  ];

  foreach ($tests as $test) {
    echo "🧪 {$test['name']}\n";

    try {
      $request = new \Illuminate\Http\Request();

      if (isset($test['data']['order'])) {
        $response = $controller->{$test['method']}($test['data']['order']);
      } else {
        $request->merge($test['data']);
        $response = $controller->{$test['method']}($request);
      }

      $result = json_decode($response->getContent(), true);
      $statusCode = $response->getStatusCode();

      echo "   📊 Status Code: {$statusCode}\n";

      if ($result['success'] ?? false) {
        echo "   ✅ Success\n";

        // Show specific response data
        if (isset($result['print_job_id'])) {
          echo "   🆔 Print Job ID: {$result['print_job_id']}\n";
        }

        if (isset($result['print_job_ids'])) {
          echo "   🆔 Print Job IDs: " . implode(', ', $result['print_job_ids']) . "\n";
        }

        if (isset($result['jobs'])) {
          echo "   📋 Queue Jobs: " . count($result['jobs']) . "\n";
        }

        if (isset($result['status'])) {
          echo "   📄 Order Status: Items=" . count($result['status']['items']) . ", Pending=" . $result['status']['pending_jobs'] . "\n";
        }
      } else {
        echo "   ❌ Failed: " . ($result['message'] ?? 'Unknown error') . "\n";
      }
    } catch (Exception $e) {
      echo "   💥 Exception: " . $e->getMessage() . "\n";
    }

    echo "\n";
    usleep(200000); // 0.2 second delay
  }

  // Test print queue management
  testQueueManagement($controller);

  // Show final statistics
  showStatistics();
}

function testQueueManagement($controller)
{
  echo "📋 Testing Print Queue Management\n";
  echo "================================\n";

  // Get pending jobs
  $pendingJobs = \App\Models\PrintQueue::pending()->limit(3)->get();

  echo "📊 Pending jobs: {$pendingJobs->count()}\n\n";

  foreach ($pendingJobs as $job) {
    echo "🖨️  Testing Job #{$job->id} ({$job->type})\n";

    try {
      // Test mark as processed
      $request = new \Illuminate\Http\Request();
      $response = $controller->markProcessed($job);
      $result = json_decode($response->getContent(), true);

      if ($result['success'] ?? false) {
        echo "   ✅ Marked as processed successfully\n";
      } else {
        echo "   ❌ Failed to mark as processed\n";
      }
    } catch (Exception $e) {
      echo "   💥 Exception: " . $e->getMessage() . "\n";
    }

    echo "\n";
  }
}

function showStatistics()
{
  echo "📊 Final Statistics\n";
  echo "==================\n";

  $stats = [
    'Total Jobs' => \App\Models\PrintQueue::count(),
    'Pending' => \App\Models\PrintQueue::where('status', 'pending')->count(),
    'Processing' => \App\Models\PrintQueue::where('status', 'processing')->count(),
    'Processed' => \App\Models\PrintQueue::where('status', 'processed')->count(),
    'Failed' => \App\Models\PrintQueue::where('status', 'failed')->count()
  ];

  foreach ($stats as $label => $count) {
    echo "📈 {$label}: {$count}\n";
  }

  echo "\n";

  // Show job types distribution  
  $types = \App\Models\PrintQueue::selectRaw('type, COUNT(*) as count')
    ->groupBy('type')
    ->get();

  echo "📄 Jobs by Type:\n";
  foreach ($types as $type) {
    echo "   🏷️  {$type->type}: {$type->count}\n";
  }

  echo "\n🎉 API Integration test completed successfully!\n";
}
