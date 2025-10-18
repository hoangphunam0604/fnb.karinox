<?php

// Test ProductService->getProductsByBranch trực tiếp
require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use App\Services\ProductService;
use App\Services\ProductDependencyService;

echo "🧪 Test ProductService->getProductsByBranch(1)\n\n";

// Khởi tạo ProductService với dependency
$dependencyService = app(ProductDependencyService::class);
$productService = new ProductService($dependencyService);

try {
    echo "🔍 Gọi getProductsByBranch(1)...\n";
    $result = $productService->getProductsByBranch(1);
    
    echo "✅ Kết quả:\n";
    echo "📊 Loại dữ liệu: " . gettype($result) . "\n";
    
    if (is_array($result)) {
        echo "📈 Số lượng nhóm: " . count($result) . "\n\n";
        
        foreach ($result as $index => $group) {
            echo "📁 Nhóm {$index}: {$group['category']}\n";
            echo "   📦 Số sản phẩm: " . count($group['products']) . "\n";
            
            foreach ($group['products'] as $productIndex => $product) {
                echo "   - Sản phẩm {$productIndex}: {$product->name} (ID: {$product->id})\n";
                echo "     💰 Regular Price: " . number_format($product->regular_price ?? 0) . " VNĐ\n";
                echo "     💸 Sale Price: " . number_format($product->sale_price ?? 0) . " VNĐ\n";
                echo "     🏷️  Final Price: " . number_format($product->final_price ?? 0) . " VNĐ\n";
                echo "     📝 Code: {$product->code}\n";
                echo "     🏪 Category: {$product->category_name}\n\n";
            }
        }
    } else if ($result instanceof \Illuminate\Support\Collection) {
        echo "📈 Kết quả: Collection với " . $result->count() . " items\n\n";
        
        foreach ($result as $index => $group) {
            echo "� Nhóm {$index}: {$group['category']}\n";
            echo "   📦 Số sản phẩm: " . $group['products']->count() . "\n";
            
            foreach ($group['products'] as $productIndex => $product) {
                echo "   - Sản phẩm {$productIndex}: {$product->name} (ID: {$product->id})\n";
                echo "     💰 Regular Price: " . number_format($product->regular_price ?? 0) . " VNĐ\n";
                echo "     💸 Sale Price: " . number_format($product->sale_price ?? 0) . " VNĐ\n";
                echo "     🏷️  Final Price: " . number_format($product->final_price ?? 0) . " VNĐ\n";
                echo "     📝 Code: {$product->code}\n";
                echo "     🏪 Category: {$product->category_name}\n\n";
            }
        }
    } else {
        echo "�📋 Dữ liệu trả về (type: " . get_class($result) . "):\n";
        if (method_exists($result, 'toArray')) {
            $array = $result->toArray();
            echo "📊 Array size: " . count($array) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "🔍 Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Kiểm tra dữ liệu thô trong database
echo "🗄️  Kiểm tra dữ liệu thô trong database:\n\n";

try {
    // Kiểm tra branches
    $branches = Branch::all(['id', 'name']);
    echo "🏪 Branches có trong DB:\n";
    foreach ($branches as $branch) {
        echo "   - ID: {$branch->id}, Tên: {$branch->name}\n";
    }
    echo "\n";
    
    // Kiểm tra products với branch_id = 1
    $products = \Illuminate\Support\Facades\DB::table('products')
        ->join('product_branches', 'products.id', '=', 'product_branches.product_id')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->where('product_branches.branch_id', 1)
        ->where('products.allows_sale', true)
        ->select('products.*', 'categories.name as category_name', 'product_branches.stock_quantity')
        ->get();
        
    echo "📦 Products cho branch_id = 1:\n";
    if ($products->count() > 0) {
        foreach ($products as $product) {
            echo "   - {$product->name} ({$product->code})\n";
            echo "     💰 Regular: " . number_format($product->regular_price ?? 0) . " VNĐ\n";
            echo "     💸 Sale: " . number_format($product->sale_price ?? 0) . " VNĐ\n";
            echo "     📁 Category: {$product->category_name}\n";
            echo "     📦 Stock: {$product->stock_quantity}\n\n";
        }
    } else {
        echo "   ❌ Không có sản phẩm nào cho branch_id = 1\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi khi kiểm tra DB: " . $e->getMessage() . "\n";
}