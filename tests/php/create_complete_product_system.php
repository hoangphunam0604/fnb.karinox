<?php

// Script tạo sản phẩm đầy đủ với tất cả các loại: ingredient, goods, processed, combo, service
require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use App\Models\ProductFormula;
use Illuminate\Support\Facades\DB;

echo "🏭 Tạo hệ thống sản phẩm đầy đủ cho F&B\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Bắt đầu transaction
    DB::beginTransaction();
    
    // Xóa dữ liệu cũ
    echo "🗑️  Xóa dữ liệu sản phẩm cũ...\n";
    ProductFormula::truncate();
    ProductBranch::truncate();
    Product::truncate();
    Category::truncate();
    
    echo "📁 Bước 1: Tạo danh mục...\n";
    
    $categories = [
        // Nguyên liệu
        [
            'name' => 'Nguyên liệu cà phê',
            'code_prefix' => 'CF_MAT',
            'description' => 'Các loại hạt cà phê, bột cà phê'
        ],
        [
            'name' => 'Nguyên liệu trà',
            'code_prefix' => 'TEA_MAT', 
            'description' => 'Lá trà các loại, bột matcha'
        ],
        [
            'name' => 'Nguyên liệu bánh',
            'code_prefix' => 'CAKE_MAT',
            'description' => 'Bột mì, đường, trứng, bơ'
        ],
        [
            'name' => 'Nguyên liệu khác',
            'code_prefix' => 'OTHER_MAT',
            'description' => 'Sữa, kem, sirô, trái cây'
        ],
        // Hàng hóa
        [
            'name' => 'Hàng hóa đóng gói',
            'code_prefix' => 'GOODS',
            'description' => 'Sản phẩm đóng gói sẵn'
        ],
        // Sản phẩm chế biến
        [
            'name' => 'Đồ uống',
            'code_prefix' => 'DRINK',
            'description' => 'Cà phê, trà, nước ép'
        ],
        [
            'name' => 'Bánh ngọt',
            'code_prefix' => 'CAKE',
            'description' => 'Bánh tươi, bánh nướng'
        ],
        [
            'name' => 'Đồ ăn',
            'code_prefix' => 'FOOD',
            'description' => 'Món ăn chế biến'
        ],
        // Combo
        [
            'name' => 'Combo',
            'code_prefix' => 'COMBO',
            'description' => 'Combo sản phẩm'
        ],
        // Dịch vụ
        [
            'name' => 'Dịch vụ',
            'code_prefix' => 'SRV',
            'description' => 'Các dịch vụ'
        ]
    ];
    
    $createdCategories = [];
    foreach ($categories as $categoryData) {
        $category = Category::create($categoryData);
        $createdCategories[$category->code_prefix] = $category;
        echo "   ✅ {$category->name}\n";
    }
    
    echo "\n🧪 Bước 2: Tạo nguyên liệu (ingredients)...\n";
    
    $ingredients = [
        // Nguyên liệu cà phê
        [
            'category' => 'CF_MAT',
            'code' => 'CF_MAT001',
            'name' => 'Hạt cà phê Arabica',
            'cost_price' => 500000, // 500k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        [
            'category' => 'CF_MAT',
            'code' => 'CF_MAT002', 
            'name' => 'Hạt cà phê Robusta',
            'cost_price' => 400000, // 400k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        [
            'category' => 'CF_MAT',
            'code' => 'CF_MAT003',
            'name' => 'Bột cà phê pha máy',
            'cost_price' => 600000, // 600k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        // Nguyên liệu trà
        [
            'category' => 'TEA_MAT',
            'code' => 'TEA_MAT001',
            'name' => 'Lá trà xanh',
            'cost_price' => 300000, // 300k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        [
            'category' => 'TEA_MAT',
            'code' => 'TEA_MAT002',
            'name' => 'Bột matcha',
            'cost_price' => 1200000, // 1.2M/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        [
            'category' => 'TEA_MAT',
            'code' => 'TEA_MAT003',
            'name' => 'Lá trà ô long',
            'cost_price' => 500000, // 500k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        // Nguyên liệu bánh
        [
            'category' => 'CAKE_MAT',
            'code' => 'CAKE_MAT001',
            'name' => 'Bột mì số 8',
            'cost_price' => 25000, // 25k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        [
            'category' => 'CAKE_MAT',
            'code' => 'CAKE_MAT002',
            'name' => 'Đường cát trắng',
            'cost_price' => 22000, // 22k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        [
            'category' => 'CAKE_MAT',
            'code' => 'CAKE_MAT003',
            'name' => 'Bơ lạt',
            'cost_price' => 180000, // 180k/kg
            'regular_price' => null,
            'unit' => 'kg'
        ],
        // Nguyên liệu khác
        [
            'category' => 'OTHER_MAT',
            'code' => 'OTHER_MAT001',
            'name' => 'Sữa tươi',
            'cost_price' => 25000, // 25k/lít
            'regular_price' => null,
            'unit' => 'lít'
        ],
        [
            'category' => 'OTHER_MAT',
            'code' => 'OTHER_MAT002',
            'name' => 'Kem whipping',
            'cost_price' => 120000, // 120k/lít
            'regular_price' => null,
            'unit' => 'lít'
        ],
        [
            'category' => 'OTHER_MAT',
            'code' => 'OTHER_MAT003',
            'name' => 'Sirô vanilla',
            'cost_price' => 80000, // 80k/chai
            'regular_price' => null,
            'unit' => 'chai'
        ]
    ];
    
    $createdIngredients = [];
    foreach ($ingredients as $ingredientData) {
        $ingredient = Product::create([
            'category_id' => $createdCategories[$ingredientData['category']]->id,
            'code' => $ingredientData['code'],
            'name' => $ingredientData['name'],
            'cost_price' => $ingredientData['cost_price'],
            'regular_price' => $ingredientData['regular_price'],
            'unit' => $ingredientData['unit'],
            'product_type' => 'ingredient',
            'allows_sale' => false, // Nguyên liệu không bán trực tiếp
            'manage_stock' => true,
            'status' => 'active'
        ]);
        
        $createdIngredients[$ingredient->code] = $ingredient;
        echo "   🧪 {$ingredient->name} - " . number_format($ingredient->cost_price) . " VNĐ/{$ingredient->unit}\n";
    }
    
    echo "\n📦 Bước 3: Tạo hàng hóa (goods)...\n";
    
    $goods = [
        [
            'category' => 'GOODS',
            'code' => 'GOODS001',
            'name' => 'Nước suối Aquafina',
            'cost_price' => 8000,
            'regular_price' => 15000,
            'description' => 'Nước suối chai 500ml'
        ],
        [
            'category' => 'GOODS',
            'code' => 'GOODS002', 
            'name' => 'Bánh quy Oreo',
            'cost_price' => 25000,
            'regular_price' => 35000,
            'description' => 'Bánh quy Oreo gói'
        ],
        [
            'category' => 'GOODS',
            'code' => 'GOODS003',
            'name' => 'Kẹo chewing gum',
            'cost_price' => 5000,
            'regular_price' => 10000,
            'description' => 'Kẹo cao su'
        ]
    ];
    
    $createdGoods = [];
    foreach ($goods as $goodData) {
        $good = Product::create([
            'category_id' => $createdCategories[$goodData['category']]->id,
            'code' => $goodData['code'],
            'name' => $goodData['name'],
            'description' => $goodData['description'],
            'cost_price' => $goodData['cost_price'],
            'regular_price' => $goodData['regular_price'],
            'product_type' => 'goods',
            'allows_sale' => true,
            'manage_stock' => true,
            'status' => 'active'
        ]);
        
        $createdGoods[$good->code] = $good;
        echo "   📦 {$good->name} - " . number_format($good->regular_price) . " VNĐ\n";
    }
    
    echo "\n🍽️  Bước 4: Tạo sản phẩm chế biến (processed)...\n";
    
    $processedProducts = [
        // Đồ uống
        [
            'category' => 'DRINK',
            'code' => 'DRINK001',
            'name' => 'Cà phê đen',
            'regular_price' => 25000,
            'sale_price' => 22000,
            'description' => 'Cà phê đen truyền thống',
            'formula' => [
                ['ingredient' => 'CF_MAT003', 'quantity' => 20], // 20g bột cà phê
                ['ingredient' => 'OTHER_MAT001', 'quantity' => 50] // 50ml sữa (cho pha)
            ]
        ],
        [
            'category' => 'DRINK',
            'code' => 'DRINK002',
            'name' => 'Cappuccino',
            'regular_price' => 35000,
            'description' => 'Cappuccino Ý truyền thống',
            'formula' => [
                ['ingredient' => 'CF_MAT003', 'quantity' => 18], // 18g bột cà phê
                ['ingredient' => 'OTHER_MAT001', 'quantity' => 150], // 150ml sữa
                ['ingredient' => 'OTHER_MAT002', 'quantity' => 30] // 30ml kem
            ]
        ],
        [
            'category' => 'DRINK',
            'code' => 'DRINK003',
            'name' => 'Trà xanh',
            'regular_price' => 25000,
            'description' => 'Trà xanh tươi mát',
            'formula' => [
                ['ingredient' => 'TEA_MAT001', 'quantity' => 3] // 3g lá trà
            ]
        ],
        [
            'category' => 'DRINK',
            'code' => 'DRINK004',
            'name' => 'Trà sữa matcha',
            'regular_price' => 40000,
            'sale_price' => 38000,
            'description' => 'Trà sữa matcha Nhật Bản',
            'formula' => [
                ['ingredient' => 'TEA_MAT002', 'quantity' => 8], // 8g bột matcha
                ['ingredient' => 'OTHER_MAT001', 'quantity' => 200], // 200ml sữa
                ['ingredient' => 'OTHER_MAT003', 'quantity' => 10] // 10ml sirô
            ]
        ],
        // Bánh ngọt
        [
            'category' => 'CAKE',
            'code' => 'CAKE001',
            'name' => 'Bánh muffin chocolate',
            'regular_price' => 25000,
            'sale_price' => 23000,
            'description' => 'Bánh muffin chocolate chip',
            'formula' => [
                ['ingredient' => 'CAKE_MAT001', 'quantity' => 50], // 50g bột mì
                ['ingredient' => 'CAKE_MAT002', 'quantity' => 30], // 30g đường
                ['ingredient' => 'CAKE_MAT003', 'quantity' => 20], // 20g bơ
                ['ingredient' => 'OTHER_MAT001', 'quantity' => 40] // 40ml sữa
            ]
        ],
        [
            'category' => 'CAKE',
            'code' => 'CAKE002',
            'name' => 'Croissant',
            'regular_price' => 20000,
            'description' => 'Bánh croissant Pháp',
            'formula' => [
                ['ingredient' => 'CAKE_MAT001', 'quantity' => 60], // 60g bột mì
                ['ingredient' => 'CAKE_MAT003', 'quantity' => 40] // 40g bơ
            ]
        ],
        // Đồ ăn
        [
            'category' => 'FOOD',
            'code' => 'FOOD001',
            'name' => 'Sandwich gà',
            'regular_price' => 35000,
            'sale_price' => 32000,
            'description' => 'Sandwich thịt gà nướng',
            'formula' => [
                ['ingredient' => 'CAKE_MAT001', 'quantity' => 80], // 80g bánh mì (từ bột mì)
                ['ingredient' => 'OTHER_MAT001', 'quantity' => 20] // 20ml sữa (cho sốt)
            ]
        ]
    ];
    
    $createdProcessed = [];
    foreach ($processedProducts as $productData) {
        // Tính cost_price từ công thức
        $costPrice = 0;
        foreach ($productData['formula'] as $formulaItem) {
            $ingredient = $createdIngredients[$formulaItem['ingredient']];
            $costPrice += ($ingredient->cost_price * $formulaItem['quantity'] / 1000); // Quy đổi về gram/ml
        }
        
        $product = Product::create([
            'category_id' => $createdCategories[$productData['category']]->id,
            'code' => $productData['code'],
            'name' => $productData['name'],
            'description' => $productData['description'],
            'cost_price' => round($costPrice),
            'regular_price' => $productData['regular_price'],
            'sale_price' => $productData['sale_price'] ?? null,
            'product_type' => 'processed',
            'allows_sale' => true,
            'print_label' => true,
            'status' => 'active'
        ]);
        
        // Tạo công thức
        foreach ($productData['formula'] as $formulaItem) {
            $ingredient = $createdIngredients[$formulaItem['ingredient']];
            ProductFormula::create([
                'product_id' => $product->id,
                'ingredient_id' => $ingredient->id,
                'quantity' => $formulaItem['quantity']
            ]);
        }
        
        $createdProcessed[$product->code] = $product;
        $priceInfo = number_format($product->regular_price) . ' VNĐ';
        if ($product->sale_price) {
            $priceInfo = number_format($product->sale_price) . ' VNĐ (giảm từ ' . number_format($product->regular_price) . ' VNĐ)';
        }
        echo "   🍽️  {$product->name} - {$priceInfo} (Cost: " . number_format($product->cost_price) . " VNĐ)\n";
    }
    
    echo "\n🎁 Bước 5: Tạo combo...\n";
    
    $combos = [
        [
            'category' => 'COMBO',
            'code' => 'COMBO001',
            'name' => 'Combo cà phê + bánh',
            'regular_price' => 45000,
            'sale_price' => 40000,
            'description' => 'Combo cà phê đen + muffin chocolate',
            'items' => [
                ['product' => 'DRINK001', 'quantity' => 1],
                ['product' => 'CAKE001', 'quantity' => 1]
            ]
        ],
        [
            'category' => 'COMBO',
            'code' => 'COMBO002',
            'name' => 'Combo healthy',
            'regular_price' => 50000,
            'description' => 'Combo trà xanh + sandwich gà',
            'items' => [
                ['product' => 'DRINK003', 'quantity' => 1],
                ['product' => 'FOOD001', 'quantity' => 1]
            ]
        ]
    ];
    
    $createdCombos = [];
    foreach ($combos as $comboData) {
        // Tính cost_price từ các sản phẩm con
        $costPrice = 0;
        foreach ($comboData['items'] as $item) {
            $childProduct = $createdProcessed[$item['product']];
            $costPrice += $childProduct->cost_price * $item['quantity'];
        }
        
        $combo = Product::create([
            'category_id' => $createdCategories[$comboData['category']]->id,
            'code' => $comboData['code'],
            'name' => $comboData['name'],
            'description' => $comboData['description'],
            'cost_price' => $costPrice,
            'regular_price' => $comboData['regular_price'],
            'sale_price' => $comboData['sale_price'] ?? null,
            'product_type' => 'combo',
            'allows_sale' => true,
            'print_label' => true,
            'status' => 'active'
        ]);
        
        // Tạo công thức combo (sử dụng bảng product_formulas)
        foreach ($comboData['items'] as $item) {
            $childProduct = $createdProcessed[$item['product']];
            ProductFormula::create([
                'product_id' => $combo->id,
                'ingredient_id' => $childProduct->id, // Ở đây ingredient_id thực chất là product con
                'quantity' => $item['quantity']
            ]);
        }
        
        $createdCombos[$combo->code] = $combo;
        $priceInfo = number_format($combo->regular_price) . ' VNĐ';
        if ($combo->sale_price) {
            $priceInfo = number_format($combo->sale_price) . ' VNĐ (giảm từ ' . number_format($combo->regular_price) . ' VNĐ)';
        }
        echo "   🎁 {$combo->name} - {$priceInfo}\n";
    }
    
    echo "\n🔧 Bước 6: Tạo dịch vụ (services)...\n";
    
    $services = [
        [
            'category' => 'SRV',
            'code' => 'SRV001',
            'name' => 'Dịch vụ giao hàng',
            'regular_price' => 15000,
            'description' => 'Phí giao hàng tận nơi'
        ],
        [
            'category' => 'SRV',
            'code' => 'SRV002',
            'name' => 'Dịch vụ đóng gói đặc biệt',
            'regular_price' => 10000,
            'description' => 'Đóng gói quà tặng'
        ],
        [
            'category' => 'SRV',
            'code' => 'SRV003',
            'name' => 'Dịch vụ đặt bàn VIP',
            'regular_price' => 50000,
            'description' => 'Phí đặt bàn VIP'
        ]
    ];
    
    $createdServices = [];
    foreach ($services as $serviceData) {
        $service = Product::create([
            'category_id' => $createdCategories[$serviceData['category']]->id,
            'code' => $serviceData['code'],
            'name' => $serviceData['name'],
            'description' => $serviceData['description'],
            'cost_price' => 0, // Dịch vụ không có cost
            'regular_price' => $serviceData['regular_price'],
            'product_type' => 'service',
            'allows_sale' => true,
            'manage_stock' => false, // Dịch vụ không quản lý tồn kho
            'status' => 'active'
        ]);
        
        $createdServices[$service->code] = $service;
        echo "   🔧 {$service->name} - " . number_format($service->regular_price) . " VNĐ\n";
    }
    
    echo "\n🏪 Bước 7: Tạo ProductBranch cho các sản phẩm bán được...\n";
    
    $branches = Branch::all();
    if ($branches->isEmpty()) {
        throw new Exception("Không tìm thấy chi nhánh nào.");
    }
    
    // Lấy tất cả sản phẩm có allows_sale = true
    $sellableProducts = Product::where('allows_sale', true)->get();
    
    foreach ($sellableProducts as $product) {
        foreach ($branches as $branch) {
            $stockQuantity = 0;
            
            // Chỉ tạo stock cho goods và ingredients
            if (in_array($product->product_type, ['goods', 'ingredient'])) {
                $stockQuantity = rand(50, 500);
            }
            
            ProductBranch::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'is_selling' => true,
                'stock_quantity' => $stockQuantity
            ]);
        }
    }
    
    // Commit transaction
    DB::commit();
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "🎉 Hoàn thành tạo hệ thống sản phẩm đầy đủ!\n";
    echo "📊 Tổng kết:\n";
    echo "   📁 Danh mục: " . count($createdCategories) . "\n";
    echo "   🧪 Nguyên liệu: " . count($createdIngredients) . "\n";
    echo "   📦 Hàng hóa: " . count($createdGoods) . "\n";
    echo "   🍽️  Sản phẩm chế biến: " . count($createdProcessed) . "\n";
    echo "   🎁 Combo: " . count($createdCombos) . "\n";
    echo "   🔧 Dịch vụ: " . count($createdServices) . "\n";
    echo "   🏪 Áp dụng cho " . $branches->count() . " chi nhánh\n";
    
    $totalProducts = Product::count();
    $totalFormulas = ProductFormula::count();
    echo "\n   📈 Tổng sản phẩm: {$totalProducts}\n";
    echo "   📋 Công thức: {$totalFormulas}\n";
    
    echo "\n✅ Hệ thống sản phẩm hoàn chỉnh đã được tạo thành công!\n";
    echo "💡 Bao gồm: nguyên liệu → chế biến → combo với đầy đủ công thức và mối quan hệ\n";
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    DB::rollback();
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}