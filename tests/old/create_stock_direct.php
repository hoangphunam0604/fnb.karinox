<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Branch;

echo "📦 TẠO TỒN KHO TRỰC TIẾP TRONG DATABASE\n";
echo "=====================================\n\n";

try {
    // Lấy branch và products
    $branch = Branch::first();
    $products = Product::all();
    
    if (!$branch) {
        echo "❌ Không tìm thấy branch\n";
        exit;
    }
    
    if ($products->isEmpty()) {
        echo "❌ Không có sản phẩm\n";
        exit;
    }
    
    echo "🏪 Branch: {$branch->name}\n";
    echo "📦 Products: {$products->count()}\n\n";
    
    echo "💾 Tạo/cập nhật ProductBranch records:\n";
    echo "====================================\n";
    
    foreach ($products as $product) {
        echo "📦 {$product->code} - {$product->name}\n";
        
        $productBranch = ProductBranch::updateOrCreate([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
        ], [
            'stock_quantity' => 100, // Tồn kho 100
            'min_stock' => 5,
            'max_stock' => 500,
            'price' => $product->regular_price,
            'cost' => $product->cost_price ?? ($product->regular_price * 0.6),
            'status' => 'active'
        ]);
        
        echo "   ✅ Stock: {$productBranch->stock_quantity}, Price: " . number_format($productBranch->price) . "đ\n\n";
    }
    
    echo "🎉 HOÀN THÀNH TẠO TỒN KHO!\n";
    echo "=========================\n";
    echo "✅ {$products->count()} sản phẩm có tồn kho\n";
    echo "✅ Mỗi sản phẩm: 100 units\n";
    echo "✅ Branch: {$branch->name}\n\n";
    
    echo "🚀 Bây giờ có thể test API stock report:\n";
    echo "http://karinox-fnb.nam/api/admin/inventory/stock-report?branch_id={$branch->id}\n\n";
    
    echo "🛒 Và test bán hàng:\n";
    echo "php test_sales_inventory_api.php\n";
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}