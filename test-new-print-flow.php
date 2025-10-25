<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\PrintRequested;
use App\Models\Invoice;

echo "🧪 Test PrintRequested event mới...\n\n";

// Lấy invoice để test
$invoice = Invoice::latest()->first();

if (!$invoice) {
  echo "❌ Không có invoice để test\n";
  exit(1);
}

echo "✅ Test với Invoice ID: {$invoice->id}\n";

// Test các type khác nhau
$testCases = [
  ['type' => 'invoice-all', 'id' => $invoice->id],
  ['type' => 'provisional', 'id' => 1], // Assume order ID 1
  ['type' => 'label', 'id' => 1],
  ['type' => 'kitchen', 'id' => 1],
];

foreach ($testCases as $case) {
  echo "\n📡 Broadcasting: {$case['type']} với ID {$case['id']}\n";

  try {
    $event = new PrintRequested($case['type'], $case['id'], $invoice->branch_id);

    echo "   ✅ Event tạo thành công\n";
    echo "   📋 Payload: type={$event->type}, id={$event->id}, branchId={$event->branchId}\n";
    echo "   📺 Channel: print-branch-{$event->branchId}\n";

    // Test broadcast (comment out nếu không có WebSocket server)
    // broadcast($event);

  } catch (\Exception $e) {
    echo "   ❌ Lỗi: {$e->getMessage()}\n";
  }
}

echo "\n✅ Test hoàn tất!\n";
echo "\n📝 Next: Test API endpoints:\n";
echo "   GET /api/print/data/invoice-all/{$invoice->id}\n";
echo "   GET /api/print/data/provisional/1\n";
echo "   GET /api/print/data/label/1\n";
echo "   GET /api/print/data/kitchen/1\n";
