<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔨 Tạo test invoice...\n\n";

// Lấy data cần thiết
$branch = Branch::first();
$user = User::first();
$products = Product::take(3)->get();

if (!$branch || !$user || $products->isEmpty()) {
    echo "❌ Thiếu data: branch, user hoặc products\n";
    exit(1);
}

// Tạo Order
$order = Order::create([
    'branch_id' => $branch->id,
    'user_id' => $user->id,
    'code' => 'ORD-TEST-' . time(),
    'table_name' => 'Bàn 1',
    'subtotal_price' => 100000,
    'total_price' => 100000,
    'status' => 'completed',
    'payment_method' => 'cash',
    'payment_status' => 'paid'
]);

echo "✅ Order created: {$order->code}\n";

// Tạo Invoice
$invoice = Invoice::create([
    'branch_id' => $branch->id,
    'order_id' => $order->id,
    'user_id' => $user->id,
    'code' => 'INV-TEST-' . time(),
    'table_name' => 'Bàn 1',
    'customer_name' => 'Khách test',
    'subtotal_price' => 100000,
    'total_price' => 100000,
    'paid_amount' => 100000,
    'change_amount' => 0,
    'tax_rate' => 0,
    'tax_amount' => 0,
    'payment_method' => 'cash',
    'payment_status' => 'paid'
]);

echo "✅ Invoice created: {$invoice->code} (ID: {$invoice->id})\n";

// Tạo Invoice Items
foreach ($products as $index => $product) {
    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_price' => $product->price ?? 30000,
        'quantity' => 1,
        'unit_price' => 30000 + ($index * 5000),
        'total_price' => 30000 + ($index * 5000)
    ]);
}

echo "✅ Created " . $products->count() . " invoice items\n\n";

echo "🧪 Bây giờ chạy: php test-print-pull-model.php\n";
