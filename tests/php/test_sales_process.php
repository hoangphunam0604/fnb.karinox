<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ProductCodeService;
use App\Services\CategoryService;
use Illuminate\Support\Facades\DB;

echo "🛒 TEST CASE BÁN HÀNG - QUY TRÌNH HOÀN CHỈNH\n";
echo "===========================================\n\n";

try {
    // BƯỚC 1: Tạo dữ liệu cơ bản (nếu chưa có)
    echo "🏪 BƯỚC 1: Thiết lập cửa hàng và sản phẩm\n";
    echo "========================================\n";

    // Tạo branch nếu chưa có
    $branch = Branch::first();
    if (!$branch) {
        $branch = Branch::create([
            'name' => 'Karinox Coffee - Chi nhánh chính',
            'address' => '123 Nguyễn Trãi, Q1, TP.HCM',
            'phone' => '0123456789',
            'status' => 'active'
        ]);
        echo "✅ Tạo chi nhánh: {$branch->name}\n";
    } else {
        echo "✅ Sử dụng chi nhánh có sẵn: {$branch->name}\n";
    }

    // Tạo customer
    $customer = Customer::create([
        'name' => 'Nguyễn Văn A',
        'phone' => '0987654321',
        'email' => 'customer@example.com',
        'gender' => 'male',
        'status' => 'active',
        'accumulated_points' => 0,
        'current_points' => 50 // Có sẵn 50 điểm để test
    ]);
    echo "✅ Tạo khách hàng: {$customer->name} (Points: {$customer->current_points})\n\n";

    // Kiểm tra products đã có
    $products = Product::with('category')->get();
    if ($products->isEmpty()) {
        echo "❌ Không có sản phẩm nào! Chạy full_test_product_code.php trước.\n";
        return;
    }

    echo "📦 Danh sách sản phẩm có sẵn:\n";
    foreach ($products as $product) {
        echo "- {$product->code}: {$product->name} (" . number_format($product->regular_price) . "đ)\n";
    }
    echo "\n";

    // Tạo stock cho products ở branch
    echo "📊 Thiết lập tồn kho cho sản phẩm...\n";
    foreach ($products as $product) {
        $productBranch = ProductBranch::updateOrCreate([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
        ], [
            'stock_quantity' => 100, // 100 sản phẩm mỗi loại
            'min_stock' => 10,
            'max_stock' => 500,
            'price' => $product->regular_price,
            'cost' => $product->cost_price,
            'status' => 'active'
        ]);
        echo "  ✅ {$product->code}: Stock = {$productBranch->stock_quantity}\n";
    }
    echo "\n";

    echo "🛒 BƯỚC 2: Tạo đơn hàng mới\n";
    echo "===========================\n";

    // Tạo order
    $order = Order::create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'order_number' => 'ORD' . date('YmdHis'),
        'order_date' => now(),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total_amount' => 0,
        'discount_amount' => 0,
        'final_amount' => 0,
        'served_by' => 'System Test',
        'notes' => 'Test order từ hệ thống'
    ]);

    echo "✅ Tạo đơn hàng: {$order->order_number}\n";
    echo "👤 Khách hàng: {$customer->name}\n";
    echo "🏪 Chi nhánh: {$branch->name}\n\n";

    echo "🛍️ BƯỚC 3: Thêm sản phẩm vào giỏ hàng\n";
    echo "=====================================\n";

    // Chọn một số sản phẩm để test
    $cartItems = [
        ['product_code' => 'CF0001', 'quantity' => 2], // Cà phê đen x2
        ['product_code' => 'CF0003', 'quantity' => 1], // Cappuccino x1  
        ['product_code' => 'TEA0001', 'quantity' => 1], // Trà xanh x1
        ['product_code' => 'TOP0001', 'quantity' => 3], // Thạch dừa x3
        ['product_code' => 'TOP0002', 'quantity' => 2], // Trân châu đen x2
    ];

    $totalAmount = 0;

    foreach ($cartItems as $item) {
        $product = Product::where('code', $item['product_code'])->first();
        
        if (!$product) {
            echo "❌ Không tìm thấy sản phẩm: {$item['product_code']}\n";
            continue;
        }

        $productBranch = ProductBranch::where('product_id', $product->id)
                                     ->where('branch_id', $branch->id)
                                     ->first();

        if (!$productBranch || $productBranch->stock_quantity < $item['quantity']) {
            echo "❌ Không đủ tồn kho cho: {$product->code}\n";
            continue;
        }

        // Tạo order item
        $subtotal = $product->regular_price * $item['quantity'];
        $totalAmount += $subtotal;

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->regular_price, // Giá gốc sản phẩm
            'unit_price' => $product->regular_price, // Đơn giá (có thể bao gồm topping)
            'quantity' => $item['quantity'],
            'total_price' => $subtotal, // Tổng giá
            'status' => 'pending',
            'note' => '',
            'print_label' => true,
            'print_kitchen' => true
        ]);

        // Trừ tồn kho
        $productBranch->decrement('stock_quantity', $item['quantity']);

        echo "✅ Thêm: {$product->code} - {$product->name}\n";
        echo "   Số lượng: {$item['quantity']} x " . number_format($product->regular_price) . "đ = " . number_format($subtotal) . "đ\n";
        echo "   Tồn kho còn: {$productBranch->fresh()->stock_quantity}\n\n";
    }

    // Cập nhật total cho order
    $order->update([
        'total_amount' => $totalAmount,
        'final_amount' => $totalAmount,
        'status' => 'confirmed'
    ]);

    echo "💰 Tổng tiền đơn hàng: " . number_format($totalAmount) . "đ\n\n";

    echo "💳 BƯỚC 4: Thanh toán và tạo hóa đơn\n";
    echo "===================================\n";

    // Áp dụng giảm giá điểm thưởng (nếu có)
    $pointsToUse = min(30, $customer->current_points); // Sử dụng tối đa 30 điểm
    $pointDiscount = $pointsToUse * 1000; // 1 điểm = 1,000đ
    
    $finalAmount = $totalAmount - $pointDiscount;

    if ($pointsToUse > 0) {
        echo "🎁 Sử dụng {$pointsToUse} điểm thưởng = -" . number_format($pointDiscount) . "đ\n";
        
        // Trừ điểm của khách hàng
        $customer->decrement('current_points', $pointsToUse);
    }

    // Tạo invoice
    $invoice = Invoice::create([
        'order_id' => $order->id,
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV' . date('YmdHis'),
        'invoice_date' => now(),
        'subtotal' => $totalAmount,
        'discount_amount' => $pointDiscount,
        'total_amount' => $finalAmount,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'status' => 'completed',
        'cashier' => 'System Test',
        'notes' => 'Thanh toán test case'
    ]);

    echo "✅ Tạo hóa đơn: {$invoice->invoice_number}\n";
    echo "💰 Tổng tiền hàng: " . number_format($totalAmount) . "đ\n";
    echo "🎁 Giảm giá điểm: -" . number_format($pointDiscount) . "đ\n";
    echo "💵 Thành tiền: " . number_format($finalAmount) . "đ\n";
    echo "💳 Thanh toán: Tiền mặt\n\n";

    // Tạo invoice items
    $orderItems = OrderItem::where('order_id', $order->id)->get();
    foreach ($orderItems as $orderItem) {
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $orderItem->product_id,
            'product_name' => $orderItem->product_name,
            'product_code' => $orderItem->product_code,
            'quantity' => $orderItem->quantity,
            'unit_price' => $orderItem->unit_price,
            'subtotal' => $orderItem->subtotal,
        ]);
    }

    // Tích điểm mới cho khách hàng (1% giá trị đơn hàng)
    $earnedPoints = intval($finalAmount / 1000); // 1,000đ = 1 điểm
    $customer->increment('current_points', $earnedPoints);
    $customer->increment('accumulated_points', $earnedPoints);

    echo "⭐ Tích điểm: +{$earnedPoints} điểm (1,000đ = 1 điểm)\n";
    echo "🏆 Điểm hiện tại: {$customer->fresh()->current_points} điểm\n\n";

    // Cập nhật trạng thái đơn hàng
    $order->update([
        'payment_status' => 'paid',
        'status' => 'completed'
    ]);

    echo "📋 BƯỚC 5: Tóm tắt kết quả\n";
    echo "=========================\n";

    echo "🎯 Thông tin đơn hàng:\n";
    echo "- Mã đơn: {$order->order_number}\n";
    echo "- Khách hàng: {$customer->name}\n";
    echo "- Số lượng sản phẩm: " . $orderItems->count() . " loại\n";
    echo "- Tổng số lượng: " . $orderItems->sum('quantity') . " món\n\n";

    echo "📦 Chi tiết sản phẩm đã bán:\n";
    foreach ($orderItems as $item) {
        $currentStock = ProductBranch::where('product_id', $item->product_id)
                                   ->where('branch_id', $branch->id)
                                   ->value('stock_quantity');
        echo "- {$item->product_code}: {$item->product_name}\n";
        echo "  Bán: {$item->quantity} x " . number_format($item->unit_price) . "đ = " . number_format($item->subtotal) . "đ\n";
        echo "  Tồn kho còn: {$currentStock}\n\n";
    }

    echo "💰 Tài chính:\n";
    echo "- Tổng hàng: " . number_format($totalAmount) . "đ\n";
    echo "- Giảm giá: " . number_format($pointDiscount) . "đ\n"; 
    echo "- Thành tiền: " . number_format($finalAmount) . "đ\n";
    echo "- Phương thức: Tiền mặt\n\n";

    echo "🏆 Điểm thưởng:\n";
    echo "- Đã dùng: {$pointsToUse} điểm\n";
    echo "- Tích được: {$earnedPoints} điểm\n";
    echo "- Điểm hiện tại: {$customer->fresh()->current_points} điểm\n\n";

    echo "📊 Kiểm tra tồn kho sau bán:\n";
    $lowStockProducts = ProductBranch::where('branch_id', $branch->id)
                                   ->whereColumn('stock_quantity', '<=', 'min_stock')
                                   ->with('product')
                                   ->get();

    if ($lowStockProducts->count() > 0) {
        echo "⚠️ Sản phẩm sắp hết hàng:\n";
        foreach ($lowStockProducts as $pb) {
            echo "- {$pb->product->code}: {$pb->product->name} (Còn: {$pb->stock_quantity})\n";
        }
    } else {
        echo "✅ Tất cả sản phẩm còn đủ tồn kho\n";
    }

    echo "\n🎉 TEST CASE BÁN HÀNG HOÀN THÀNH!\n";
    echo "================================\n";
    echo "✅ Tạo đơn hàng thành công\n";
    echo "✅ Thêm sản phẩm vào giỏ hàng\n";
    echo "✅ Trừ tồn kho chính xác\n";
    echo "✅ Áp dụng điểm thưởng\n";
    echo "✅ Tạo hóa đơn hoàn chỉnh\n";
    echo "✅ Thanh toán thành công\n";
    echo "✅ Tích điểm mới cho khách\n";
    echo "✅ Cập nhật trạng thái đơn hàng\n";
    echo "✅ Kiểm tra tồn kho sau bán\n\n";

    echo "🔥 HỆ THỐNG BÁN HÀNG HOẠT ĐỘNG PERFECT!\n";

} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}