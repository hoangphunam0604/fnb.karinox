<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

// Khởi tạo Laravel app
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🛠️  Tạo sample Order + Invoice để test hybrid print...\n\n";

try {
    DB::beginTransaction();
    
    // 1. Lấy customer đầu tiên hoặc tạo mới
    $customer = Customer::first();
    if (!$customer) {
        $customer = Customer::create([
            'fullname' => 'Test Customer',
            'phone' => '0901234567',
            'email' => 'test@test.com',
            'gender' => 'male',
            'status' => 'active'
        ]);
        echo "✅ Tạo customer: {$customer->fullname}\n";
    } else {
        echo "✅ Sử dụng customer: {$customer->fullname}\n";
    }
    
    // 2. Lấy sản phẩm có giá
    $product = Product::where('regular_price', '>', 0)->first();
    if (!$product) {
        echo "❌ Không có sản phẩm nào có giá > 0\n";
        exit(1);
    }
    
    $price = $product->sale_price ?: $product->regular_price;
    echo "✅ Sử dụng sản phẩm: {$product->name} - " . number_format($price) . "đ\n";
    
    // 3. Tạo Order
    $order = Order::create([
        'code' => 'TEST' . date('YmdHis'),
        'customer_id' => $customer->id,
        'branch_id' => 1,
        'table_id' => 1,
        'order_status' => 'completed',
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'subtotal_price' => $price,
        'total_price' => $price,
        'paid_at' => now(),
        'ordered_at' => now()
    ]);
    
    echo "✅ Tạo order: {$order->code}\n";
    
    // 4. Tạo Order Item
    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'unit_price' => $price,
        'total_price' => $price,
        'status' => 'accepted'
    ]);
    
    echo "✅ Tạo order item: {$orderItem->quantity}x {$product->name}\n";
    
    // 5. Tạo Invoice
    $invoice = Invoice::create([
        'code' => 'INV' . date('YmdHis'),
        'customer_id' => $customer->id,
        'branch_id' => 1,
        'subtotal_amount' => $price,
        'total_amount' => $price,
        'payment_method' => 'cash',
        'issued_at' => now(),
        'status' => 'paid'
    ]);
    
    echo "✅ Tạo invoice: {$invoice->code}\n";
    
    // 6. Link Invoice với Order
    $order->update(['invoice_id' => $invoice->id]);
    
    echo "✅ Liên kết Order #{$order->id} với Invoice #{$invoice->id}\n";
    
    DB::commit();
    
    echo "\n🎉 Sample data đã được tạo thành công!\n";
    echo "📋 Order ID: {$order->id} - {$order->code}\n";
    echo "🧾 Invoice ID: {$invoice->id} - {$invoice->code}\n";
    echo "💰 Tổng tiền: " . number_format($invoice->total_amount) . "đ\n\n";
    
    echo "🚀 Bây giờ có thể chạy hybrid print test!\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}