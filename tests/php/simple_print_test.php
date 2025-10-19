<?php

/**
 * Simple Print System Test
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Simple Print System Test\n";
echo "===========================\n\n";

// Tạo dữ liệu test đơn giản
createSimpleTestData();

// Test các chức năng print
testPrintFeatures();

function createSimpleTestData()
{
  echo "🔧 Creating simple test data...\n";

  // Lấy hoặc tạo order đầu tiên
  $order = \App\Models\Order::first();

  if (!$order) {
    echo "❌ No orders found. Please create some orders first.\n";
    return;
  }

  // Cập nhật order items để có print flags
  $items = $order->items()->limit(3)->get();

  foreach ($items as $index => $item) {
    $item->update([
      'print_label' => ($index % 2 == 0), // Item chẵn cần in tem
      'print_kitchen' => ($index % 3 == 0), // Item chia hết cho 3 cần in bếp
      'printed_label' => false,
      'printed_kitchen' => false,
      'notes' => $index == 0 ? 'Không đường, nhiều đá' : null
    ]);
  }

  echo "   ✅ Updated {$items->count()} order items with print flags\n";
  echo "   🆔 Using Order ID: {$order->id} ({$order->order_code})\n\n";

  return $order;
}

function testPrintFeatures()
{
  $printService = new \App\Services\PrintService();
  $order = \App\Models\Order::with(['items', 'branch'])->first();

  if (!$order) {
    echo "❌ No order to test with\n";
    return;
  }

  echo "📋 Testing with Order: {$order->order_code}\n";
  echo "   Branch: " . ($order->branch->name ?? 'Unknown') . "\n";
  echo "   Items: {$order->items->count()}\n\n";

  // Test 1: Print Provisional
  echo "🧪 Test 1: Print Provisional Bill\n";
  try {
    $result = $printService->printProvisional($order, 'test_printer_001');

    if ($result['success']) {
      echo "   ✅ Success - Job ID: {$result['print_job_id']}\n";
      echo "   📄 Content length: " . strlen($result['content']) . " chars\n";
    } else {
      echo "   ❌ Failed: {$result['message']}\n";
    }
  } catch (Exception $e) {
    echo "   💥 Exception: " . $e->getMessage() . "\n";
  }
  echo "\n";

  // Test 2: Print Labels
  echo "🧪 Test 2: Print Labels\n";
  try {
    $result = $printService->printLabels($order, null, 'label_printer_001');

    if ($result['success']) {
      echo "   ✅ Success - Jobs: " . count($result['print_job_ids']) . "\n";
      echo "   🏷️  Items with labels: {$result['items_count']}\n";
    } else {
      echo "   ❌ Failed: {$result['message']}\n";
    }
  } catch (Exception $e) {
    echo "   💥 Exception: " . $e->getMessage() . "\n";
  }
  echo "\n";

  // Test 3: Print Kitchen Tickets
  echo "🧪 Test 3: Print Kitchen Tickets\n";
  try {
    $result = $printService->printKitchenTickets($order, null, 'kitchen_printer_001');

    if ($result['success']) {
      echo "   ✅ Success - Jobs: " . count($result['print_job_ids']) . "\n";
      echo "   👨‍🍳 Kitchen items: {$result['items_count']}\n";
    } else {
      echo "   ❌ Failed: {$result['message']}\n";
    }
  } catch (Exception $e) {
    echo "   💥 Exception: " . $e->getMessage() . "\n";
  }
  echo "\n";

  // Test 4: Auto Print
  echo "🧪 Test 4: Auto Print\n";
  try {
    $results = $printService->autoPrint($order, 'auto_printer_001');

    echo "   ✅ Auto print completed\n";
    foreach ($results as $type => $result) {
      if ($result['success']) {
        echo "   📄 {$type}: Success\n";
      } else {
        echo "   ❌ {$type}: {$result['message']}\n";
      }
    }
  } catch (Exception $e) {
    echo "   💥 Exception: " . $e->getMessage() . "\n";
  }
  echo "\n";

  // Test 5: Check Print Queue
  echo "🧪 Test 5: Print Queue Status\n";
  try {
    $pendingJobs = \App\Models\PrintQueue::pending()->get();
    echo "   📋 Pending jobs: {$pendingJobs->count()}\n";

    foreach ($pendingJobs as $job) {
      echo "   🖨️  Job #{$job->id}: {$job->type} (priority: {$job->priority})\n";
    }

    // Process first few jobs
    if ($pendingJobs->count() > 0) {
      echo "\n   🔄 Processing first 3 jobs...\n";

      foreach ($pendingJobs->take(3) as $job) {
        try {
          $job->update(['status' => 'processing']);
          usleep(100000); // 0.1 second delay
          $job->markAsProcessed();
          echo "   ✅ Processed job #{$job->id} ({$job->type})\n";
        } catch (Exception $e) {
          $job->markAsFailed($e->getMessage());
          echo "   ❌ Failed job #{$job->id}: " . $e->getMessage() . "\n";
        }
      }
    }
  } catch (Exception $e) {
    echo "   💥 Exception: " . $e->getMessage() . "\n";
  }
  echo "\n";

  // Test 6: Print Templates
  echo "🧪 Test 6: Print Templates\n";
  try {
    $templates = \App\Models\PrintTemplate::where('is_active', true)->get();
    echo "   📄 Available templates: {$templates->count()}\n";

    foreach ($templates as $template) {
      echo "   📋 {$template->type}: {$template->name}\n";
    }
  } catch (Exception $e) {
    echo "   💥 Exception: " . $e->getMessage() . "\n";
  }
  echo "\n";

  // Summary
  echo "📊 Test Summary\n";
  echo "===============\n";

  $totalJobs = \App\Models\PrintQueue::count();
  $processedJobs = \App\Models\PrintQueue::where('status', 'processed')->count();
  $pendingJobs = \App\Models\PrintQueue::where('status', 'pending')->count();
  $failedJobs = \App\Models\PrintQueue::where('status', 'failed')->count();

  echo "📈 Print Queue Stats:\n";
  echo "   Total jobs: {$totalJobs}\n";
  echo "   Processed: {$processedJobs}\n";
  echo "   Pending: {$pendingJobs}\n";
  echo "   Failed: {$failedJobs}\n\n";

  echo "🎉 Print system test completed!\n";
}
