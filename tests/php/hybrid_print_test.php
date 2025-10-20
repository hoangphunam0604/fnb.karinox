<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Order;
use App\Models\Invoice;
use App\Http\POS\Controllers\PrintController;
use Illuminate\Http\Request;

// Khởi tạo Laravel app
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing HYBRID Print Approach (Order vs Invoice)...\n\n";

try {
  // Lấy 1 order có invoice_id để test
  $order = Order::whereNotNull('invoice_id')->first();
  if (!$order || !$order->invoice_id) {
    echo "❌ Không có order nào với Invoice để test\n";
    echo "💡 Hãy chạy thanh toán 1 order trước để tạo Invoice\n";
    exit(1);
  }

  // Lấy Invoice separately
  $invoice = \App\Models\Invoice::find($order->invoice_id);

  echo "📋 Test Data:\n";
  echo "   Order ID: {$order->id} - {$order->code}\n";
  echo "   Invoice ID: {$invoice->id} - {$invoice->code}\n";
  echo "   Payment Status: {$order->payment_status->value}\n\n";

  $controller = new PrintController();

  // Test 1: Print từ Order (approach cũ)
  echo "🖨️  TEST 1: Print từ Order (Fast POS workflow)\n";
  echo "============================================\n";
  $response1 = $controller->invoice($order->id);
  $data1 = $response1->getData(true);

  if ($data1['success']) {
    echo "✅ Print từ Order: {$data1['message']}\n";
    echo "   Print ID: {$data1['data']['print_id']}\n";
    echo "   Source: Order data\n";
  } else {
    echo "❌ Print từ Order failed: {$data1['message']}\n";
  }

  echo "\n";

  // Test 2: Print từ Invoice (approach mới) 
  echo "🧾 TEST 2: Print từ Invoice (100% accurate data)\n";
  echo "==============================================\n";
  $response2 = $controller->printFromInvoice($invoice->id);
  $data2 = $response2->getData(true);

  if ($data2['success']) {
    echo "✅ Print từ Invoice: {$data2['message']}\n";
    echo "   Print ID: {$data2['data']['print_id']}\n";
    echo "   Source: Invoice data (100% accurate)\n";
    echo "   Invoice ID: {$data2['data']['invoice_id']}\n";
  } else {
    echo "❌ Print từ Invoice failed: {$data2['message']}\n";
  }

  echo "\n";

  // So sánh Print History
  echo "📊 So sánh Print History\n";
  echo "========================\n";

  $printHistories = \App\Models\PrintHistory::whereJsonContains('metadata->order_id', $order->id)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

  echo "Print histories cho Order #{$order->id}:\n";
  foreach ($printHistories as $history) {
    $source = $history->metadata['printed_from'] ?? 'order';
    $invoiceId = $history->metadata['invoice_id'] ?? 'N/A';

    echo "   - Print ID: {$history->print_id}\n";
    echo "     Type: {$history->type}\n";
    echo "     Source: {$source}\n";
    echo "     Invoice ID: {$invoiceId}\n";
    echo "     Status: {$history->status}\n";
    echo "     Created: {$history->created_at}\n\n";
  }

  // Đề xuất sử dụng
  echo "💡 ĐƯỜNG HƯỚNG SỬ DỤNG\n";
  echo "=====================\n";
  echo "✅ Print từ ORDER:\n";
  echo "   - In tạm tính (provisional)\n";
  echo "   - In nhanh cho POS workflow\n";
  echo "   - In phiếu bếp\n";
  echo "   - Auto-print sau thanh toán\n\n";

  echo "✅ Print từ INVOICE:\n";
  echo "   - Re-print hóa đơn chính thức\n";
  echo "   - Audit trail với data chính xác 100%\n";
  echo "   - In lại sau khi có điều chỉnh\n";
  echo "   - Compliance với yêu cầu kế toán\n\n";
} catch (\Exception $e) {
  echo "❌ Test failed: " . $e->getMessage() . "\n";
  echo "Stack trace: " . $e->getTraceAsString() . "\n";
  exit(1);
}

echo "🎉 HYBRID Print System test hoàn thành!\n";
echo "✨ Bây giờ bạn có thể chọn print từ Order HOẶC Invoice tùy nhu cầu!\n";
