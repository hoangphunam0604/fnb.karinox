<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Order;
use App\Http\POS\Controllers\PrintController;
use Illuminate\Http\Request;

// Khởi tạo Laravel app
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing POS PrintController...\n\n";

try {
  // Lấy 1 order bất kỳ để test
  $order = Order::first();
  if (!$order) {
    echo "❌ Không có order nào để test\n";
    exit(1);
  }

  echo "📋 Order ID: {$order->id}\n";
  echo "📋 Order Code: {$order->code}\n";
  echo "💰 Payment Status: {$order->payment_status->value}\n\n";

  // Test khởi tạo controller
  $controller = new PrintController();
  echo "✅ PrintController khởi tạo thành công\n";

  // Test method provisional (không cần thanh toán)
  echo "\n🖨️  Testing provisional print...\n";
  $response = $controller->provisional($order->id);
  $data = $response->getData(true);

  if ($data['success']) {
    echo "✅ Provisional print: {$data['message']}\n";
    echo "   Print ID: {$data['data']['print_id']}\n";
  } else {
    echo "❌ Provisional print failed: {$data['message']}\n";
  }

  // Test getPrintStatus  
  echo "\n📊 Testing print status...\n";
  $response = $controller->getPrintStatus($order->id);
  $data = $response->getData(true);

  if ($data['success']) {
    echo "✅ Print status retrieved successfully\n";
    if (count($data['data']) > 0) {
      foreach ($data['data'] as $type => $status) {
        echo "   {$type}: {$status['status']}\n";
      }
    } else {
      echo "   No print history found\n";
    }
  } else {
    echo "❌ Get print status failed: {$data['message']}\n";
  }
} catch (\Exception $e) {
  echo "❌ Test failed: " . $e->getMessage() . "\n";
  echo "Stack trace: " . $e->getTraceAsString() . "\n";
  exit(1);
}

echo "\n🎉 POS PrintController test hoàn thành!\n";
