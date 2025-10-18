<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use App\Models\ProductFormula;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
  protected ProductService $productService;

  public function __construct(ProductService $productService)
  {
    $this->productService = $productService;
  }

  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Xóa dữ liệu cũ theo đúng thứ tự foreign key
    ProductFormula::query()->delete();
    ProductBranch::query()->delete();
    Product::query()->delete();
    Category::query()->delete();

    echo "🏭 Tạo hệ thống sản phẩm đầy đủ cho F&B...\n";

    // Bước 1: Tạo danh mục
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
    }

    // Bước 2: Tạo nguyên liệu (ingredients)
    $ingredients = [
      // Nguyên liệu cà phê
      [
        'category' => 'CF_MAT',
        'code' => 'CF_MAT001',
        'name' => 'Hạt cà phê Arabica',
        'cost_price' => 500000, // 500k/kg
        'unit' => 'kg'
      ],
      [
        'category' => 'CF_MAT',
        'code' => 'CF_MAT002',
        'name' => 'Hạt cà phê Robusta',
        'cost_price' => 400000, // 400k/kg
        'unit' => 'kg'
      ],
      [
        'category' => 'CF_MAT',
        'code' => 'CF_MAT003',
        'name' => 'Bột cà phê pha máy',
        'cost_price' => 600000, // 600k/kg
        'unit' => 'kg'
      ],
      // Nguyên liệu trà
      [
        'category' => 'TEA_MAT',
        'code' => 'TEA_MAT001',
        'name' => 'Lá trà xanh',
        'cost_price' => 300000, // 300k/kg
        'unit' => 'kg'
      ],
      [
        'category' => 'TEA_MAT',
        'code' => 'TEA_MAT002',
        'name' => 'Bột matcha',
        'cost_price' => 1200000, // 1.2M/kg
        'unit' => 'kg'
      ],
      [
        'category' => 'TEA_MAT',
        'code' => 'TEA_MAT003',
        'name' => 'Lá trà ô long',
        'cost_price' => 500000, // 500k/kg
        'unit' => 'kg'
      ],
      // Nguyên liệu bánh
      [
        'category' => 'CAKE_MAT',
        'code' => 'CAKE_MAT001',
        'name' => 'Bột mì số 8',
        'cost_price' => 25000, // 25k/kg
        'unit' => 'kg'
      ],
      [
        'category' => 'CAKE_MAT',
        'code' => 'CAKE_MAT002',
        'name' => 'Đường cát trắng',
        'cost_price' => 22000, // 22k/kg
        'unit' => 'kg'
      ],
      [
        'category' => 'CAKE_MAT',
        'code' => 'CAKE_MAT003',
        'name' => 'Bơ lạt',
        'cost_price' => 180000, // 180k/kg
        'unit' => 'kg'
      ],
      // Nguyên liệu khác
      [
        'category' => 'OTHER_MAT',
        'code' => 'OTHER_MAT001',
        'name' => 'Sữa tươi',
        'cost_price' => 25000, // 25k/lít
        'unit' => 'lít'
      ],
      [
        'category' => 'OTHER_MAT',
        'code' => 'OTHER_MAT002',
        'name' => 'Kem whipping',
        'cost_price' => 120000, // 120k/lít
        'unit' => 'lít'
      ],
      [
        'category' => 'OTHER_MAT',
        'code' => 'OTHER_MAT003',
        'name' => 'Sirô vanilla',
        'cost_price' => 80000, // 80k/chai
        'unit' => 'chai'
      ]
    ];

    $createdIngredients = [];
    foreach ($ingredients as $ingredientData) {
      // Tạo dữ liệu cho ProductService
      $branches = Branch::all();
      $branchesData = [];
      foreach ($branches as $branch) {
        $branchesData[] = [
          'branch_id' => $branch->id,
          'is_selling' => false, // Nguyên liệu không bán trực tiếp
          'stock_quantity' => rand(500, 2000) // Nguyên liệu cần nhiều stock cho sản xuất
        ];
      }

      $productData = [
        'category_id' => $createdCategories[$ingredientData['category']]->id,
        'code' => $ingredientData['code'],
        'name' => $ingredientData['name'],
        'cost_price' => $ingredientData['cost_price'],
        'regular_price' => null,
        'unit' => $ingredientData['unit'],
        'product_type' => 'ingredient',
        'allows_sale' => false, // Nguyên liệu không bán trực tiếp
        'manage_stock' => true,
        'status' => 'active',
        'branches' => $branchesData
      ];

      $ingredient = $this->productService->create($productData);
      $createdIngredients[$ingredient->code] = $ingredient;
    }

    // Bước 3: Tạo hàng hóa (goods)
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
      $branches = Branch::all();
      $branchesData = [];
      foreach ($branches as $branch) {
        $branchesData[] = [
          'branch_id' => $branch->id,
          'is_selling' => true,
          'stock_quantity' => rand(50, 200) // Hàng hóa có sẵn để bán
        ];
      }

      $productData = [
        'category_id' => $createdCategories[$goodData['category']]->id,
        'code' => $goodData['code'],
        'name' => $goodData['name'],
        'description' => $goodData['description'],
        'cost_price' => $goodData['cost_price'],
        'regular_price' => $goodData['regular_price'],
        'product_type' => 'goods',
        'allows_sale' => true,
        'manage_stock' => true,
        'status' => 'active',
        'branches' => $branchesData
      ];

      $good = $this->productService->create($productData);
      $createdGoods[$good->code] = $good;
    }

    // Bước 4: Tạo sản phẩm chế biến (processed)
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
        'name' => 'Latte',
        'regular_price' => 38000,
        'sale_price' => 35000,
        'description' => 'Latte mềm mại, thơm ngon',
        'formula' => [
          ['ingredient' => 'CF_MAT003', 'quantity' => 16],
          ['ingredient' => 'OTHER_MAT001', 'quantity' => 180],
          ['ingredient' => 'OTHER_MAT003', 'quantity' => 5]
        ]
      ],
      [
        'category' => 'DRINK',
        'code' => 'DRINK004',
        'name' => 'Trà xanh',
        'regular_price' => 25000,
        'description' => 'Trà xanh tươi mát',
        'formula' => [
          ['ingredient' => 'TEA_MAT001', 'quantity' => 3] // 3g lá trà
        ]
      ],
      [
        'category' => 'DRINK',
        'code' => 'DRINK005',
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
      [
        'category' => 'DRINK',
        'code' => 'DRINK006',
        'name' => 'Trà ô long',
        'regular_price' => 28000,
        'sale_price' => 25000,
        'description' => 'Trà ô long thơm nức',
        'formula' => [
          ['ingredient' => 'TEA_MAT003', 'quantity' => 4]
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
      [
        'category' => 'CAKE',
        'code' => 'CAKE003',
        'name' => 'Bánh tiramisu',
        'regular_price' => 45000,
        'sale_price' => 42000,
        'description' => 'Bánh tiramisu Ý nguyên bản',
        'formula' => [
          ['ingredient' => 'CAKE_MAT001', 'quantity' => 40],
          ['ingredient' => 'CAKE_MAT002', 'quantity' => 25],
          ['ingredient' => 'CAKE_MAT003', 'quantity' => 30],
          ['ingredient' => 'OTHER_MAT001', 'quantity' => 60],
          ['ingredient' => 'OTHER_MAT002', 'quantity' => 50]
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
      ],
      [
        'category' => 'FOOD',
        'code' => 'FOOD002',
        'name' => 'Salad trộn',
        'regular_price' => 30000,
        'description' => 'Salad rau củ tươi ngon',
        'formula' => [
          ['ingredient' => 'OTHER_MAT001', 'quantity' => 30] // 30ml sữa (cho dressing)
        ]
      ]
    ];

    $createdProcessed = [];
    foreach ($processedProducts as $productData) {
      // Tính cost_price từ công thức
      $costPrice = 0;
      $formulas = [];
      foreach ($productData['formula'] as $formulaItem) {
        $ingredient = $createdIngredients[$formulaItem['ingredient']];
        $costPrice += ($ingredient->cost_price * $formulaItem['quantity'] / 1000); // Quy đổi về gram/ml

        // Chuẩn bị dữ liệu formulas cho ProductService
        $formulas[] = [
          'ingredient_id' => $ingredient->id,
          'quantity' => $formulaItem['quantity']
        ];
      }

      $branches = Branch::all();
      $branchesData = [];
      foreach ($branches as $branch) {
        $branchesData[] = [
          'branch_id' => $branch->id,
          'is_selling' => true,
          'stock_quantity' => 0 // Hàng chế biến không lưu kho
        ];
      }

      $productServiceData = [
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
        'status' => 'active',
        'branches' => $branchesData,
        'formulas' => $formulas
      ];

      $product = $this->productService->create($productServiceData);
      $createdProcessed[$product->code] = $product;
    }

    // Bước 5: Tạo combo
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
          ['product' => 'DRINK004', 'quantity' => 1],
          ['product' => 'FOOD001', 'quantity' => 1]
        ]
      ],
      [
        'category' => 'COMBO',
        'code' => 'COMBO003',
        'name' => 'Combo premium',
        'regular_price' => 70000,
        'sale_price' => 65000,
        'description' => 'Combo latte + tiramisu',
        'items' => [
          ['product' => 'DRINK003', 'quantity' => 1],
          ['product' => 'CAKE003', 'quantity' => 1]
        ]
      ]
    ];

    $createdCombos = [];
    foreach ($combos as $comboData) {
      // Tính cost_price từ các sản phẩm con
      $costPrice = 0;
      $formulas = [];
      foreach ($comboData['items'] as $item) {
        $childProduct = $createdProcessed[$item['product']];
        $costPrice += $childProduct->cost_price * $item['quantity'];

        // Chuẩn bị dữ liệu formulas cho combo
        $formulas[] = [
          'ingredient_id' => $childProduct->id, // Ở đây ingredient_id thực chất là product con
          'quantity' => $item['quantity']
        ];
      }

      $branches = Branch::all();
      $branchesData = [];
      foreach ($branches as $branch) {
        $branchesData[] = [
          'branch_id' => $branch->id,
          'is_selling' => true,
          'stock_quantity' => 0 // Combo không lưu kho
        ];
      }

      $comboServiceData = [
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
        'status' => 'active',
        'branches' => $branchesData,
        'formulas' => $formulas
      ];

      $combo = $this->productService->create($comboServiceData);
      $createdCombos[$combo->code] = $combo;
    }

    // Bước 6: Tạo dịch vụ (services)
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
      $branches = Branch::all();
      $branchesData = [];
      foreach ($branches as $branch) {
        $branchesData[] = [
          'branch_id' => $branch->id,
          'is_selling' => true,
          'stock_quantity' => 0 // Dịch vụ không có tồn kho
        ];
      }

      $serviceServiceData = [
        'category_id' => $createdCategories[$serviceData['category']]->id,
        'code' => $serviceData['code'],
        'name' => $serviceData['name'],
        'description' => $serviceData['description'],
        'cost_price' => 0, // Dịch vụ không có cost
        'regular_price' => $serviceData['regular_price'],
        'product_type' => 'service',
        'allows_sale' => true,
        'manage_stock' => false, // Dịch vụ không quản lý tồn kho
        'status' => 'active',
        'branches' => $branchesData
      ];

      $service = $this->productService->create($serviceServiceData);
      $createdServices[$service->code] = $service;
    }

    echo "✅ Product seeder completed successfully!\n";
    echo "📊 Created:\n";
    echo "   - Categories: " . count($createdCategories) . "\n";
    echo "   - Ingredients: " . count($createdIngredients) . "\n";
    echo "   - Goods: " . count($createdGoods) . "\n";
    echo "   - Processed Products: " . count($createdProcessed) . "\n";
    echo "   - Combos: " . count($createdCombos) . "\n";
    echo "   - Services: " . count($createdServices) . "\n";
    echo "   - Product Formulas: " . ProductFormula::count() . "\n";
    echo "   - Product Branches: " . ProductBranch::count() . "\n";
  }
}
