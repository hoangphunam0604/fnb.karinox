<?php

// Tạo dữ liệu test cho ProductService
require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;

echo "🏗️  Tạo dữ liệu test cho ProductService\n\n";

try {
    // Kiểm tra branch có tồn tại
    $branch = Branch::find(1);
    if (!$branch) {
        echo "❌ Branch với ID 1 không tồn tại\n";
        exit(1);
    }
    echo "✅ Branch tồn tại: {$branch->name}\n";

    // Lấy category có sẵn hoặc tạo mới
    $category = Category::where('code_prefix', 'CF')->first();
    if (!$category) {
        $category = Category::create([
            'name' => 'Đồ uống test',
            'code_prefix' => 'DR'
        ]);
    }
    echo "✅ Category: {$category->name} (ID: {$category->id}, Prefix: {$category->code_prefix})\n";

    // Tạo một số sản phẩm test
    $products = [
        [
            'code' => 'CF001',
            'name' => 'Cà phê đen',
            'regular_price' => 25000,
            'sale_price' => 20000,
            'cost_price' => 10000
        ],
        [
            'code' => 'CF002', 
            'name' => 'Cà phê sữa',
            'regular_price' => 30000,
            'sale_price' => 25000,
            'cost_price' => 12000
        ],
        [
            'code' => 'CF003',
            'name' => 'Cappuccino',
            'regular_price' => 35000,
            'sale_price' => null, // Không có giá sale
            'cost_price' => 15000
        ]
    ];

    echo "\n📦 Tạo sản phẩm:\n";
    foreach ($products as $productData) {
        $product = Product::firstOrCreate([
            'code' => $productData['code']
        ], [
            'name' => $productData['name'],
            'category_id' => $category->id,
            'regular_price' => $productData['regular_price'],
            'sale_price' => $productData['sale_price'],
            'cost_price' => $productData['cost_price'],
            'allows_sale' => true,
            'product_type' => 'processed'
        ]);

        echo "   ✅ {$product->name} (ID: {$product->id})\n";

        // Tạo ProductBranch cho branch ID 1
        $productBranch = ProductBranch::firstOrCreate([
            'product_id' => $product->id,
            'branch_id' => 1
        ], [
            'is_selling' => true,
            'stock_quantity' => 100
        ]);

        echo "      🏪 ProductBranch tạo cho branch 1 (Stock: {$productBranch->stock_quantity})\n";
    }

    echo "\n🎉 Hoàn thành tạo dữ liệu test!\n";
    echo "🔍 Bây giờ có thể test ProductService->getProductsByBranch(1)\n";

} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}