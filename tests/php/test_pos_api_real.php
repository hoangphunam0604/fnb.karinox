<?php

// Test POS API với thực tế database
require_once __DIR__ . '/../../bootstrap/app.php';

use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔐 Tạo user test và lấy token JWT...\n";

// Tạo hoặc lấy user test
$user = User::firstOrCreate([
  'email' => 'test@pos.com'
], [
  'name' => 'POS Test User',
  'password' => bcrypt('password'),
  'status' => 'active'
]);

echo "👤 User: {$user->name} (ID: {$user->id})\n";

// Tạo hoặc lấy branch test
$branch = Branch::firstOrCreate([
  'name' => 'Chi nhánh test'
], [
  'code' => 'BR001',
  'address' => 'Test address',
  'phone' => '0123456789',
  'status' => 'active'
]);

echo "🏪 Branch: {$branch->name} (ID: {$branch->id})\n";

// Tạo category test
$category = Category::firstOrCreate([
  'name' => 'Đồ uống test'
], [
  'code' => 'CAT001',
  'status' => 'active'
]);

// Tạo product test
$product = Product::firstOrCreate([
  'code' => 'CF001TEST'
], [
  'name' => 'Cà phê test',
  'category_id' => $category->id,
  'regular_price' => 30000,
  'sale_price' => 25000,
  'cost_price' => 15000,
  'allows_sale' => true,
  'product_type' => 'processed',
  'status' => 'active'
]);

// Tạo ProductBranch
ProductBranch::firstOrCreate([
  'product_id' => $product->id,
  'branch_id' => $branch->id
], [
  'is_selling' => true,
  'stock_quantity' => 100
]);

echo "📦 Product: {$product->name} (ID: {$product->id})\n";

// Tạo JWT token
try {
  $token = auth('api')->login($user);
  echo "🔑 JWT Token: {$token}\n\n";
} catch (Exception $e) {
  echo "❌ Lỗi tạo JWT token: " . $e->getMessage() . "\n";
  exit(1);
}

// Test API
$baseUrl = 'http://localhost/karinox-fnb/public';
$url = $baseUrl . '/api/pos/products';

$headers = [
  'Authorization: Bearer ' . $token,
  'karinox-app-id: karinox-app-pos',
  'X-Karinox-Branch-Id: ' . $branch->id,
  'Content-Type: application/json',
  'Accept: application/json'
];

echo "🔗 Testing API: {$url}\n";
echo "🏪 Branch ID: {$branch->id}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Response Status: {$httpCode}\n";

if ($error) {
  echo "❌ cURL Error: {$error}\n";
  exit(1);
}

if ($response) {
  echo "📦 Response Body:\n";
  $data = json_decode($response, true);
  if ($data) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Kiểm tra kết quả
    if ($httpCode === 200 && isset($data['success']) && $data['success']) {
      echo "\n✅ API hoạt động bình thường!\n";
      if (isset($data['data']) && count($data['data']) > 0) {
        echo "📊 Tìm thấy " . count($data['data']) . " nhóm sản phẩm\n";
      } else {
        echo "⚠️  Không có dữ liệu sản phẩm\n";
      }
    } else {
      echo "\n❌ API trả về lỗi\n";
    }
  } else {
    echo "Raw response: " . $response;
  }
  echo "\n";
} else {
  echo "❌ Empty response\n";
}
