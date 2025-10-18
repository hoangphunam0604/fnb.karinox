<?php

// Script tạo dữ liệu mẫu hoàn chỉnh cho hệ thống F&B
require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use App\Models\Area;
use App\Models\TableAndRoom;
use App\Models\User;
use App\Models\MembershipLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "🏗️  Tạo dữ liệu mẫu hoàn chỉnh cho hệ thống F&B\n";
echo str_repeat("=", 70) . "\n\n";

try {
  // Bắt đầu transaction
  DB::beginTransaction();

  echo "🏪 Bước 1: Tạo chi nhánh...\n";

  $branches = [
    [
      'name' => 'Karinox Coffee - Chi nhánh chính',
      'code' => 'KRX001',
      'address' => '123 Nguyễn Huệ, Q1, TP.HCM',
      'phone' => '028-3823-4567',
      'email' => 'chinhanh1@karinox.vn',
      'manager_name' => 'Nguyễn Văn A',
      'status' => 'active'
    ],
    [
      'name' => 'Karinox Coffee - Chi nhánh 2',
      'code' => 'KRX002',
      'address' => '456 Lê Lợi, Q3, TP.HCM',
      'phone' => '028-3823-4568',
      'email' => 'chinhanh2@karinox.vn',
      'manager_name' => 'Trần Thị B',
      'status' => 'active'
    ]
  ];

  $createdBranches = [];
  foreach ($branches as $branchData) {
    $branch = Branch::create($branchData);
    $createdBranches[] = $branch;
    echo "   ✅ {$branch->name}\n";
  }

  echo "\n👥 Bước 2: Tạo user quản lý...\n";

  $users = [
    [
      'username' => 'admin',
      'fullname' => 'Quản trị viên',
      'password' => Hash::make('password'),
      'current_branch' => $createdBranches[0]->id,
      'is_active' => true
    ],
    [
      'username' => 'manager1',
      'fullname' => 'Quản lý chi nhánh 1',
      'password' => Hash::make('password'),
      'current_branch' => $createdBranches[0]->id,
      'is_active' => true
    ],
    [
      'username' => 'manager2',
      'fullname' => 'Quản lý chi nhánh 2',
      'password' => Hash::make('password'),
      'current_branch' => $createdBranches[1]->id,
      'is_active' => true
    ]
  ];

  foreach ($users as $userData) {
    $user = User::create($userData);
    echo "   ✅ {$user->fullname} ({$user->username})\n";
  }

  echo "\n🏆 Bước 3: Tạo cấp độ thành viên...\n";

  $membershipLevels = [
    [
      'name' => 'Thành viên mới',
      'rank' => 1,
      'min_spent' => 0,
      'max_spent' => 999999,
      'reward_multiplier' => 1.0
    ],
    [
      'name' => 'Thành viên bạc',
      'rank' => 2,
      'min_spent' => 1000000,
      'max_spent' => 4999999,
      'reward_multiplier' => 1.2
    ],
    [
      'name' => 'Thành viên vàng',
      'rank' => 3,
      'min_spent' => 5000000,
      'max_spent' => 9999999,
      'reward_multiplier' => 1.5
    ],
    [
      'name' => 'Thành viên kim cương',
      'rank' => 4,
      'min_spent' => 10000000,
      'max_spent' => null,
      'reward_multiplier' => 2.0
    ]
  ];

  foreach ($membershipLevels as $levelData) {
    $level = MembershipLevel::create($levelData);
    echo "   ✅ {$level->name} (Từ {$level->min_points} điểm, giảm {$level->discount_percentage}%)\n";
  }

  echo "\n📁 Bước 4: Tạo danh mục sản phẩm...\n";

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

  echo "\n🍽️  Bước 5: Tạo sản phẩm mẫu...\n";

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
      ]
    ]
  ];

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
        foreach ($createdBranches as $branch) {
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

  echo "\n🏢 Bước 6: Tạo khu vực và bàn...\n";

  foreach ($createdBranches as $branch) {
    echo "\n   🏪 Chi nhánh: {$branch->name}\n";

    // Tạo các khu vực
    $areasData = [
      [
        'name' => 'Tầng trệt',
        'description' => 'Khu vực tầng trệt, gần quầy bar'
      ],
      [
        'name' => 'Tầng 2',
        'description' => 'Khu vực tầng 2, yên tĩnh'
      ],
      [
        'name' => 'Sân thượng',
        'description' => 'Khu vực sân thượng, view đẹp'
      ]
    ];

    foreach ($areasData as $areaData) {
      $area = Area::create([
        'branch_id' => $branch->id,
        'name' => $areaData['name'],
        'description' => $areaData['description']
      ]);

      echo "      📍 Khu vực: {$area->name}\n";

      // Tạo bàn cho mỗi khu vực
      $tableCount = rand(5, 8); // Mỗi khu vực có 5-8 bàn
      for ($i = 1; $i <= $tableCount; $i++) {
        $table = TableAndRoom::create([
          'area_id' => $area->id,
          'name' => "Bàn {$i}",
          'capacity' => rand(2, 6), // Sức chứa 2-6 người
          'status' => 'available'
        ]);

        echo "         🪑 {$table->name} ({$table->capacity} chỗ)\n";
      }
    }
  }

  // Commit transaction
  DB::commit();

  echo "\n" . str_repeat("=", 70) . "\n";
  echo "🎉 Hoàn thành tạo dữ liệu mẫu!\n";
  echo "📊 Tổng kết:\n";
  echo "   🏪 Chi nhánh: " . count($createdBranches) . "\n";
  echo "   👥 Users: " . count($users) . "\n";
  echo "   🏆 Cấp độ thành viên: " . count($membershipLevels) . "\n";
  echo "   📁 Danh mục: " . count($createdCategories) . "\n";
  echo "   🍽️  Sản phẩm: {$totalProducts}\n";

  // Đếm tổng số khu vực và bàn
  $totalAreas = Area::count();
  $totalTables = TableAndRoom::count();
  echo "   📍 Khu vực: {$totalAreas}\n";
  echo "   🪑 Bàn: {$totalTables}\n";

  echo "\n✅ Dữ liệu mẫu đã được tạo thành công!\n";
  echo "\n🔑 Thông tin đăng nhập:\n";
  echo "   - Admin: username=admin, password=password\n";
  echo "   - Manager 1: username=manager1, password=password\n";
  echo "   - Manager 2: username=manager2, password=password\n";
} catch (Exception $e) {
  // Rollback nếu có lỗi
  DB::rollback();
  echo "❌ Lỗi: " . $e->getMessage() . "\n";
  echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
  echo "🔍 Stack trace:\n" . $e->getTraceAsString() . "\n";
  exit(1);
}
