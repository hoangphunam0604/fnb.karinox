<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ProductCodeService;
use App\Services\CategoryService;
use App\Models\Category;
use App\Models\Product;

echo "🧪 TEST AUTO-GENERATE PRODUCT CODE\n";
echo "===================================\n\n";

$codeService = new ProductCodeService();
$categoryService = new CategoryService($codeService);

// Test 1: Generate prefix từ tên category
echo "📋 Test 1: Generate Prefix từ tên category\n";
echo "==========================================\n";

$testNames = [
  'Cà phê',
  'Trà xanh',
  'Sữa tươi',
  'Topping',
  'Bánh ngọt',
  'Nước ép',
  'Đá viên',
  'Gia vị'
];

foreach ($testNames as $name) {
  $prefix = $codeService->generatePrefixFromName($name);
  echo "'{$name}' -> '{$prefix}'\n";
}

echo "\n";

// Test 2: Generate product code
echo "📋 Test 2: Generate Product Code\n";
echo "=================================\n";

// Giả lập có category với prefix
echo "Giả sử có categories:\n";
echo "- ID 1: 'Cà phê' (prefix: CF)\n";
echo "- ID 2: 'Trà' (prefix: TEA)\n";
echo "- ID 3: 'Sữa' (prefix: MILK)\n\n";

// Mock data (trong thực tế sẽ từ database)
$mockCategories = [
  1 => 'CF',
  2 => 'TEA',
  3 => 'MILK'
];

foreach ($mockCategories as $categoryId => $prefix) {
  echo "Category {$categoryId} ({$prefix}):\n";

  // Simulate tạo 3 sản phẩm liên tiếp
  for ($i = 1; $i <= 3; $i++) {
    // Trong thực tế sẽ gọi qua database
    echo "  Sản phẩm {$i}: {$prefix}" . str_pad($i, 4, '0', STR_PAD_LEFT) . "\n";
  }
  echo "\n";
}

// Test 3: Code validation
echo "📋 Test 3: Code Validation\n";
echo "==========================\n";

$testCodes = [
  'CF0001' => 'Valid',
  'TEA0023' => 'Valid',
  'MILK1234' => 'Valid',
  'CF01' => 'Invalid (số không đủ 4 digits)',
  'cf0001' => 'Invalid (prefix phải uppercase)',
  'ABCDEFGH0001' => 'Invalid (prefix quá dài)',
  'CF' => 'Invalid (thiếu số)',
  '0001' => 'Invalid (thiếu prefix)'
];

foreach ($testCodes as $code => $expected) {
  $isValid = $codeService->isValidProductCode($code);
  $result = $isValid ? 'Valid' : 'Invalid';
  $status = $result === explode(' ', $expected)[0] ? '✅' : '❌';
  echo "{$status} '{$code}' -> {$result} ({$expected})\n";
}

echo "\n";

// Test 4: Extract number from code
echo "📋 Test 4: Extract Number from Code\n";
echo "====================================\n";

$testExtracts = [
  ['CF0001', 'CF'] => 1,
  ['TEA0023', 'TEA'] => 23,
  ['MILK1234', 'MILK'] => 1234,
  ['TOP0099', 'TOP'] => 99
];

$reflection = new ReflectionClass($codeService);
$method = $reflection->getMethod('extractNumberFromCode');
$method->setAccessible(true);

foreach ($testExtracts as $input => $expected) {
  [$code, $prefix] = array_keys($input);
  $result = $method->invoke($codeService, $code, $prefix);
  $status = $result === $expected ? '✅' : '❌';
  echo "{$status} extractNumberFromCode('{$code}', '{$prefix}') -> {$result} (expected: {$expected})\n";
}

echo "\n🎉 Test completed!\n";
echo "\n💡 Để test với database thật:\n";
echo "1. Tạo vài categories với code_prefix\n";
echo "2. Tạo products và xem code được generate\n";
echo "3. Xóa product ở giữa, tạo product mới xem có fill gap không\n";
