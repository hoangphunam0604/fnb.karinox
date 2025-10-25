<?php

use App\Models\Invoice;
use App\Services\PrintHistoryService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Tìm invoice mới nhất
$invoice = Invoice::latest()->first();

if (!$invoice) {
    echo "❌ Không tìm thấy invoice nào\n";
    exit(1);
}

echo "✅ Tìm thấy invoice: {$invoice->code} (ID: {$invoice->id})\n\n";

// Test PrintHistoryService
$service = new PrintHistoryService();

echo "📝 Đang tạo print targets...\n";
$result = $service->createPrintJobsForInvoice($invoice);

echo "\n✅ Kết quả:\n";
echo "Invoice ID: {$result['invoice_id']}\n";
echo "Invoice Code: {$result['invoice_code']}\n";
echo "Branch ID: {$result['branch_id']}\n";
echo "Số lượng targets: " . count($result['targets']) . "\n\n";

echo "📋 Danh sách targets:\n";
foreach ($result['targets'] as $index => $target) {
    echo "\nTarget #" . ($index + 1) . ":\n";
    echo "  - ID: {$target['id']}\n";
    echo "  - Type: {$target['type']}\n";
    echo "  - Priority: {$target['priority']}\n";
    echo "  - Printer Type: {$target['printer_type']}\n";
}

echo "\n✅ Test hoàn tất!\n";
