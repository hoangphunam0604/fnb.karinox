<?php

// Script tạo sản phẩm mẫu cho hệ thống F&B
require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use Illuminate\Support\Facades\DB;

echo "🍽️  Tạo sản phẩm mẫu cho hệ thống F&B\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Bắt đầu transaction
    DB::beginTransaction();
    
    echo "📁 Bước 1: Tạo danh mục sản phẩm...\n";
    
    $categories = [
        [
            'name' => 'Cà phê',
            'code_prefix' => 'CF',
            'description' => 'Các loại cà phê truyền thống và hiện đại'
        ],
        [
            'name' => 'Trà & Trà sữa',
            'code_prefix' => 'TEA',
            'description' => 'Trà các loại và trà sữa'
        ],
        [
            'name' => 'Nước ép & Sinh tố',
            'code_prefix' => 'JUI',
            'description' => 'Nước ép trái cây tươi và sinh tố'
        ],
        [
            'name' => 'Bánh & Dessert',
            'code_prefix' => 'CAKE',
            'description' => 'Bánh ngọt và tráng miệng'
        ],
        [
            'name' => 'Đồ ăn nhẹ',
            'code_prefix' => 'SNACK',
            'description' => 'Các món ăn nhẹ, khai vị'
        ]
    ];
    
    $createdCategories = [];
    foreach ($categories as $categoryData) {
        $category = Category::create($categoryData);
        $createdCategories[] = $category;
        echo "   ✅ {$category->name} (Prefix: {$category->code_prefix})\n";
    }
    
    echo "\n🍽️  Bước 2: Tạo sản phẩm mẫu...\n";
    
    // Sản phẩm cho từng danh mục
    $productsByCategory = [
        'CF' => [ // Cà phê
            [
                'code' => 'CF001',
                'name' => 'Cà phê đen',
                'regular_price' => 25000,
                'sale_price' => 22000,
                'cost_price' => 8000,
                'description' => 'Cà phê đen truyền thống, đậm đà'
            ],
            [
                'code' => 'CF002',
                'name' => 'Cà phê sữa',
                'regular_price' => 30000,
                'sale_price' => 28000,
                'cost_price' => 10000,
                'description' => 'Cà phê sữa đậm đà, ngọt ngào'
            ],
            [
                'code' => 'CF003',
                'name' => 'Cappuccino',
                'regular_price' => 35000,
                'sale_price' => null,
                'cost_price' => 12000,
                'description' => 'Cappuccino Ý truyền thống'
            ],
            [
                'code' => 'CF004',
                'name' => 'Latte',
                'regular_price' => 38000,
                'sale_price' => 35000,
                'cost_price' => 13000,
                'description' => 'Latte mềm mại, thơm ngon'
            ],
            [
                'code' => 'CF005',
                'name' => 'Americano',
                'regular_price' => 32000,
                'sale_price' => null,
                'cost_price' => 10000,
                'description' => 'Americano đậm vị, thanh thoát'
            ],
            [
                'code' => 'CF006',
                'name' => 'Mocha',
                'regular_price' => 40000,
                'sale_price' => 38000,
                'cost_price' => 14000,
                'description' => 'Mocha chocolate đậm đà'
            ]
        ],
        'TEA' => [ // Trà & Trà sữa
            [
                'code' => 'TEA001',
                'name' => 'Trà sữa truyền thống',
                'regular_price' => 35000,
                'sale_price' => 32000,
                'cost_price' => 12000,
                'description' => 'Trà sữa đậm đà với trân châu'
            ],
            [
                'code' => 'TEA002',
                'name' => 'Trà xanh',
                'regular_price' => 25000,
                'sale_price' => null,
                'cost_price' => 8000,
                'description' => 'Trà xanh tươi mát'
            ],
            [
                'code' => 'TEA003',
                'name' => 'Trà ô long',
                'regular_price' => 28000,
                'sale_price' => 25000,
                'cost_price' => 9000,
                'description' => 'Trà ô long thơm nức'
            ],
            [
                'code' => 'TEA004',
                'name' => 'Trà sữa matcha',
                'regular_price' => 40000,
                'sale_price' => 38000,
                'cost_price' => 15000,
                'description' => 'Trà sữa matcha Nhật Bản'
            ],
            [
                'code' => 'TEA005',
                'name' => 'Trà đào cam sả',
                'regular_price' => 32000,
                'sale_price' => 30000,
                'cost_price' => 11000,
                'description' => 'Trà đào cam sả tươi mát'
            ]
        ],
        'JUI' => [ // Nước ép & Sinh tố
            [
                'code' => 'JUI001',
                'name' => 'Nước ép cam',
                'regular_price' => 30000,
                'sale_price' => 28000,
                'cost_price' => 12000,
                'description' => 'Nước ép cam tươi 100%'
            ],
            [
                'code' => 'JUI002',
                'name' => 'Sinh tố bơ',
                'regular_price' => 35000,
                'sale_price' => null,
                'cost_price' => 15000,
                'description' => 'Sinh tố bơ béo ngậy'
            ],
            [
                'code' => 'JUI003',
                'name' => 'Nước ép dưa hấu',
                'regular_price' => 25000,
                'sale_price' => 22000,
                'cost_price' => 10000,
                'description' => 'Nước ép dưa hấu tươi mát'
            ],
            [
                'code' => 'JUI004',
                'name' => 'Sinh tố xoài',
                'regular_price' => 38000,
                'sale_price' => 35000,
                'cost_price' => 16000,
                'description' => 'Sinh tố xoài ngọt lịm'
            ],
            [
                'code' => 'JUI005',
                'name' => 'Nước ép dứa',
                'regular_price' => 28000,
                'sale_price' => null,
                'cost_price' => 11000,
                'description' => 'Nước ép dứa tươi chua ngọt'
            ]
        ],
        'CAKE' => [ // Bánh & Dessert
            [
                'code' => 'CAKE001',
                'name' => 'Bánh tiramisu',
                'regular_price' => 45000,
                'sale_price' => 42000,
                'cost_price' => 20000,
                'description' => 'Bánh tiramisu Ý nguyên bản'
            ],
            [
                'code' => 'CAKE002',
                'name' => 'Bánh cheesecake',
                'regular_price' => 40000,
                'sale_price' => null,
                'cost_price' => 18000,
                'description' => 'Bánh phô mai New York'
            ],
            [
                'code' => 'CAKE003',
                'name' => 'Muffin chocolate',
                'regular_price' => 25000,
                'sale_price' => 23000,
                'cost_price' => 10000,
                'description' => 'Bánh muffin chocolate chip'
            ],
            [
                'code' => 'CAKE004',
                'name' => 'Bánh red velvet',
                'regular_price' => 42000,
                'sale_price' => 40000,
                'cost_price' => 19000,
                'description' => 'Bánh red velvet mềm mịn'
            ],
            [
                'code' => 'CAKE005',
                'name' => 'Croissant',
                'regular_price' => 20000,
                'sale_price' => null,
                'cost_price' => 8000,
                'description' => 'Bánh croissant Pháp giòn tan'
            ]
        ],
        'SNACK' => [ // Đồ ăn nhẹ
            [
                'code' => 'SNACK001',
                'name' => 'Sandwich gà',
                'regular_price' => 35000,
                'sale_price' => 32000,
                'cost_price' => 15000,
                'description' => 'Sandwich thịt gà nướng'
            ],
            [
                'code' => 'SNACK002',
                'name' => 'Salad trộn',
                'regular_price' => 30000,
                'sale_price' => null,
                'cost_price' => 12000,
                'description' => 'Salad rau củ tươi ngon'
            ],
            [
                'code' => 'SNACK003',
                'name' => 'Bánh mì chảo',
                'regular_price' => 28000,
                'sale_price' => 25000,
                'cost_price' => 10000,
                'description' => 'Bánh mì chảo trứng thơm ngon'
            ],
            [
                'code' => 'SNACK004',
                'name' => 'Khoai tây chiên',
                'regular_price' => 22000,
                'sale_price' => 20000,
                'cost_price' => 8000,
                'description' => 'Khoai tây chiên giòn rụm'
            ]
        ]
    ];
    
    // Lấy tất cả chi nhánh để tạo ProductBranch
    $branches = Branch::all();
    if ($branches->isEmpty()) {
        throw new Exception("Không tìm thấy chi nhánh nào. Vui lòng tạo chi nhánh trước.");
    }
    
    $totalProducts = 0;
    foreach ($createdCategories as $category) {
        $prefix = $category->code_prefix;
        if (isset($productsByCategory[$prefix])) {
            echo "\n   📁 Tạo sản phẩm cho danh mục: {$category->name}\n";
            
            foreach ($productsByCategory[$prefix] as $productData) {
                // Tạo product
                $product = Product::create([
                    'category_id' => $category->id,
                    'code' => $productData['code'],
                    'name' => $productData['name'],
                    'description' => $productData['description'] ?? null,
                    'regular_price' => $productData['regular_price'],
                    'sale_price' => $productData['sale_price'],
                    'cost_price' => $productData['cost_price'],
                    'allows_sale' => true,
                    'product_type' => 'processed',
                    'print_label' => true,
                    'status' => 'active'
                ]);
                
                // Tạo ProductBranch cho tất cả các chi nhánh
                foreach ($branches as $branch) {
                    ProductBranch::create([
                        'product_id' => $product->id,
                        'branch_id' => $branch->id,
                        'is_selling' => true,
                        'stock_quantity' => rand(50, 200) // Random stock từ 50-200
                    ]);
                }
                
                $priceInfo = number_format($product->regular_price) . ' VNĐ';
                if ($product->sale_price) {
                    $priceInfo = number_format($product->sale_price) . ' VNĐ (giảm từ ' . number_format($product->regular_price) . ' VNĐ)';
                }
                
                echo "      ✅ {$product->name} - {$priceInfo}\n";
                $totalProducts++;
            }
        }
    }
    
    // Commit transaction
    DB::commit();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 Hoàn thành tạo sản phẩm mẫu!\n";
    echo "📊 Tổng kết:\n";
    echo "   📁 Danh mục: " . count($createdCategories) . "\n";
    echo "   🍽️  Sản phẩm: {$totalProducts}\n";
    echo "   🏪 Áp dụng cho " . $branches->count() . " chi nhánh\n";
    
    echo "\n✅ Dữ liệu sản phẩm mẫu đã được tạo thành công!\n";
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    DB::rollback();
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}