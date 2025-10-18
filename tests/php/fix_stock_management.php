<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductBranch;

echo "🔧 KÍCH HOẠT MANAGE_STOCK CHO TẤT CẢ PRODUCTS\n";
echo "============================================\n\n";

try {
    // Update all products to manage_stock = true
    $updated = Product::query()->update(['manage_stock' => true]);
    echo "✅ Kích hoạt manage_stock cho {$updated} sản phẩm\n\n";
    
    // Update ProductBranch với giá thực tế
    $productBranches = ProductBranch::with('product')->get();
    
    echo "💰 Cập nhật giá cho ProductBranch:\n";
    echo "===============================\n";
    
    foreach ($productBranches as $pb) {
        $basePrice = match($pb->product->code ?? '') {
            'CF0001' => 25000,  // Cà phê đen
            'CF0003' => 45000,  // Cappuccino
            'CF0004' => 35000,  // Americano
            'TEA0001' => 20000, // Trà xanh
            'TEA0002' => 25000, // Trà ô long
            'MILK0001' => 15000, // Sữa tươi
            'MILK0002' => 20000, // Sữa chocolate
            'TOP0001' => 5000,  // Thạch dừa
            'TOP0002' => 8000,  // Trân châu đen
            'TOP0003' => 10000, // Pudding
            'CAKE0001' => 15000, // Bánh mì ngọt
            default => 25000
        };
        
        $pb->update([
            'price' => $basePrice,
            'cost' => intval($basePrice * 0.6),
            'status' => 'active'
        ]);
        
        echo "✅ {$pb->product->code}: " . number_format($basePrice) . "đ (Stock: {$pb->stock_quantity})\n";
    }
    
    echo "\n🎉 HOÀN THÀNH CẬP NHẬT!\n";
    echo "======================\n";
    echo "✅ Tất cả products có manage_stock = true\n";
    echo "✅ ProductBranch có giá thực tế\n";
    echo "✅ Sẵn sàng cho stock report API\n\n";
    
    echo "🚀 Test ngay:\n";
    echo "php test_sales_inventory_api.php\n";
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}