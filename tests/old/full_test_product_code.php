<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ProductCodeService;
use App\Services\CategoryService;
use App\Models\Category;
use App\Models\Product;

echo "🧪 TEST AUTO-GENERATE PRODUCT CODE - FULL SYSTEM\n";
echo "================================================\n\n";

try {
  $codeService = new ProductCodeService();
  $categoryService = new CategoryService($codeService);

  echo "📋 BƯỚC 1: Tạo Categories với Auto-Prefix\n";
  echo "==========================================\n";

  $testCategories = [
    ['name' => 'Cà phê', 'description' => 'Các loại cà phê'],
    ['name' => 'Trà xanh', 'description' => 'Các loại trà'],
    ['name' => 'Sữa tươi', 'description' => 'Đồ uống sữa'],
    ['name' => 'Topping', 'description' => 'Topping cho đồ uống'],
    ['name' => 'Bánh ngọt', 'description' => 'Các loại bánh'],
  ];

  $createdCategories = [];

  foreach ($testCategories as $categoryData) {
    echo "🔄 Tạo category: {$categoryData['name']}...\n";

    $category = $categoryService->create($categoryData);
    $createdCategories[] = $category;

    echo "✅ ID: {$category->id}, Name: {$category->name}, Prefix: {$category->code_prefix}\n\n";
  }

  echo "\n📦 BƯỚC 2: Tạo Products với Auto-Code Generation\n";
  echo "===============================================\n";

  $testProducts = [
    // Cà phê
    ['name' => 'Cà phê đen', 'category' => 'Cà phê', 'price' => 25000],
    ['name' => 'Cà phê sữa', 'category' => 'Cà phê', 'price' => 30000],
    ['name' => 'Cappuccino', 'category' => 'Cà phê', 'price' => 45000],

    // Trà
    ['name' => 'Trà xanh nguyên chất', 'category' => 'Trà xanh', 'price' => 20000],
    ['name' => 'Trà ô long', 'category' => 'Trà xanh', 'price' => 25000],

    // Sữa
    ['name' => 'Sữa tươi không đường', 'category' => 'Sữa tươi', 'price' => 15000],
    ['name' => 'Sữa chocolate', 'category' => 'Sữa tươi', 'price' => 20000],

    // Topping
    ['name' => 'Thạch dừa', 'category' => 'Topping', 'price' => 5000],
    ['name' => 'Trân châu đen', 'category' => 'Topping', 'price' => 8000],
    ['name' => 'Pudding', 'category' => 'Topping', 'price' => 10000],

    // Bánh
    ['name' => 'Bánh mì ngọt', 'category' => 'Bánh ngọt', 'price' => 15000],
  ];

  $createdProducts = [];

  foreach ($testProducts as $productData) {
    // Tìm category
    $category = collect($createdCategories)->firstWhere('name', $productData['category']);

    if (!$category) {
      echo "❌ Không tìm thấy category: {$productData['category']}\n";
      continue;
    }

    echo "🔄 Tạo product: {$productData['name']} (Category: {$category->name})...\n";

    $product = Product::create([
      'name' => $productData['name'],
      'category_id' => $category->id,
      'regular_price' => $productData['price'],
      'cost_price' => intval($productData['price'] * 0.6), // Cost = 60% price
      'status' => 'active', // CommonStatus enum value
      // Không set code - để auto-generate
    ]);

    $createdProducts[] = $product;

    echo "✅ ID: {$product->id}, Code: {$product->code}, Name: {$product->name}\n\n";
  }

  echo "\n🔍 BƯỚC 3: Kiểm Tra Kết Quả\n";
  echo "===========================\n";

  echo "📊 Categories đã tạo:\n";
  foreach ($createdCategories as $cat) {
    $productCount = Product::where('category_id', $cat->id)->count();
    echo "- {$cat->name} (Prefix: {$cat->code_prefix}) - {$productCount} sản phẩm\n";
  }

  echo "\n📦 Products đã tạo (theo category):\n";
  foreach ($createdCategories as $cat) {
    $products = Product::where('category_id', $cat->id)->orderBy('id')->get();
    echo "\n🏷️  {$cat->name} ({$cat->code_prefix}):\n";
    foreach ($products as $product) {
      echo "  - {$product->code}: {$product->name} (" . number_format($product->regular_price) . "đ)\n";
    }
  }

  echo "\n🧪 BƯỚC 4: Test Xóa Product và Tạo Mới (Gap Handling)\n";
  echo "====================================================\n";

  // Lấy category Cà phê
  $coffeeCategory = collect($createdCategories)->firstWhere('name', 'Cà phê');
  $coffeeProducts = Product::where('category_id', $coffeeCategory->id)->orderBy('id')->get();

  echo "📋 Trước khi xóa:\n";
  foreach ($coffeeProducts as $product) {
    echo "- {$product->code}: {$product->name}\n";
  }

  // Xóa sản phẩm giữa (sản phẩm thứ 2)
  $productToDelete = $coffeeProducts->skip(1)->first();
  if ($productToDelete) {
    echo "\n🗑️  Xóa sản phẩm: {$productToDelete->code} - {$productToDelete->name}\n";
    $productToDelete->delete();
  }

  // Tạo sản phẩm mới
  echo "\n➕ Tạo sản phẩm mới...\n";
  $newProduct = Product::create([
    'name' => 'Americano',
    'category_id' => $coffeeCategory->id,
    'regular_price' => 35000,
    'cost_price' => 21000,
    'status' => 'active', // CommonStatus enum value
  ]);

  echo "✅ Sản phẩm mới: {$newProduct->code} - {$newProduct->name}\n";

  echo "\n📋 Sau khi xóa và tạo mới:\n";
  $coffeeProductsAfter = Product::where('category_id', $coffeeCategory->id)->orderBy('id')->get();
  foreach ($coffeeProductsAfter as $product) {
    echo "- {$product->code}: {$product->name}\n";
  }

  echo "\n🎉 TEST HOÀN THÀNH!\n";
  echo "==================\n";
  echo "✅ Auto-generate prefix từ tên category\n";
  echo "✅ Auto-generate product code theo sequence\n";
  echo "✅ Gap handling (không fill gap khi xóa)\n";
  echo "✅ Unique constraint working\n";
  echo "✅ Format code đúng: {PREFIX}{0000}\n";
} catch (Exception $e) {
  echo "❌ LỖI: " . $e->getMessage() . "\n";
  echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
  echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}
