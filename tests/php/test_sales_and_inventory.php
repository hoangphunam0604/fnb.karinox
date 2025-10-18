<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductBranch;

// Load Laravel app
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🛒 Test Sales and Inventory Management\n";
echo "=====================================\n\n";

// 1. Kiểm tra stock trước khi bán
echo "📦 Stock status before sales:\n";
$goodsWithStock = ProductBranch::with('product')
    ->whereHas('product', function($q) { 
        $q->where('product_type', 'goods'); 
    })
    ->where('stock_quantity', '>', 0)
    ->where('branch_id', 1)
    ->get();

foreach ($goodsWithStock as $pb) {
    echo "   - {$pb->product->name} ({$pb->product->code}): {$pb->stock_quantity} units\n";
}

// 2. Simulate sales - Bán 5 chai nước suối
$waterProduct = Product::where('code', 'GOODS001')->first();
$waterStock = ProductBranch::where('product_id', $waterProduct->id)
    ->where('branch_id', 1)
    ->first();

$saleQuantity = 5;
$oldStock = $waterStock->stock_quantity;

echo "\n💰 Simulating sale:\n";
echo "   - Product: {$waterProduct->name}\n";
echo "   - Quantity sold: $saleQuantity\n";
echo "   - Stock before: $oldStock\n";

// Update stock
$newStock = $waterStock->stock_quantity - $saleQuantity;
ProductBranch::where('product_id', $waterProduct->id)
    ->where('branch_id', 1)
    ->update(['stock_quantity' => $newStock]);

echo "   - Stock after: $newStock\n";

// 3. Simulate sales - Bán 3 gói bánh quy
$cookieProduct = Product::where('code', 'GOODS002')->first();
$cookieStock = ProductBranch::where('product_id', $cookieProduct->id)
    ->where('branch_id', 1)
    ->first();

$saleQuantity2 = 3;
$oldStock2 = $cookieStock->stock_quantity;

echo "\n💰 Simulating another sale:\n";
echo "   - Product: {$cookieProduct->name}\n";
echo "   - Quantity sold: $saleQuantity2\n";
echo "   - Stock before: $oldStock2\n";

$newStock2 = $cookieStock->stock_quantity - $saleQuantity2;
ProductBranch::where('product_id', $cookieProduct->id)
    ->where('branch_id', 1)
    ->update(['stock_quantity' => $newStock2]);

echo "   - Stock after: $newStock2\n";

// 4. Check final stock status
echo "\n📦 Final stock status:\n";
$finalGoodsStock = ProductBranch::with('product')
    ->whereHas('product', function($q) { 
        $q->where('product_type', 'goods'); 
    })
    ->where('branch_id', 1)
    ->get();

foreach ($finalGoodsStock as $pb) {
    $status = $pb->stock_quantity > 0 ? "✅ In Stock" : "❌ Out of Stock";
    echo "   - {$pb->product->name}: {$pb->stock_quantity} units $status\n";
}

// 5. Simulate low stock alert
echo "\n⚠️  Low Stock Alert (< 10 units):\n";
$lowStockItems = ProductBranch::with('product')
    ->where('stock_quantity', '>', 0)
    ->where('stock_quantity', '<', 10)
    ->where('branch_id', 1)
    ->get();

if ($lowStockItems->count() > 0) {
    foreach ($lowStockItems as $pb) {
        echo "   - {$pb->product->name}: {$pb->stock_quantity} units (Need restock!)\n";
    }
} else {
    echo "   - No low stock items found\n";
}

// 6. Test processed product availability (should depend on ingredients)
echo "\n🔧 Checking processed product availability:\n";
$processedProducts = Product::where('product_type', 'processed')->take(3)->get();

foreach ($processedProducts as $product) {
    echo "   - {$product->name} ({$product->code}):\n";
    
    // Check if product has formulas (ingredients)
    $formulas = $product->formulas()->with('ingredient')->get();
    if ($formulas->count() > 0) {
        $canMake = true;
        echo "     Ingredients needed:\n";
        
        foreach ($formulas as $formula) {
            $ingredientStock = ProductBranch::where('product_id', $formula->ingredient_id)
                ->where('branch_id', 1)
                ->first();
            
            $available = $ingredientStock ? $ingredientStock->stock_quantity : 0;
            $needed = $formula->quantity;
            $status = $available >= $needed ? "✅" : "❌";
            
            if ($available < $needed) {
                $canMake = false;
            }
            
            echo "       - {$formula->ingredient->name}: {$needed}g needed, {$available}g available $status\n";
        }
        
        $availability = $canMake ? "✅ Can be made" : "❌ Cannot be made (insufficient ingredients)";
        echo "     Status: $availability\n\n";
    } else {
        echo "     No formula found\n\n";
    }
}

echo "✅ Sales and inventory test completed!\n";