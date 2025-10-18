<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductBranch;

// Load Laravel app
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📦 Test Inventory Import API\n";
echo "============================\n\n";

// 1. Kiểm tra sản phẩm trước khi nhập kho
$productId = 411;
$product = Product::find($productId);

if (!$product) {
  // Tìm sản phẩm khác có ID thực tế
  $product = Product::where('product_type', 'ingredient')->first();
  $productId = $product->id;
  echo "⚠️  Product ID 411 not found, using {$product->code} (ID: {$productId}) instead\n\n";
}

echo "📋 Product Info:\n";
echo "   - ID: {$product->id}\n";
echo "   - Code: {$product->code}\n";
echo "   - Name: {$product->name}\n";
echo "   - Type: {$product->product_type->value}\n\n";

// Kiểm tra stock trước khi nhập
$stockBefore = ProductBranch::where('product_id', $productId)
  ->where('branch_id', 1)
  ->value('stock_quantity') ?? 0;

echo "📦 Stock before import: {$stockBefore}\n\n";

// 2. Test InventoryService trực tiếp trước
echo "🧪 Testing InventoryService directly:\n";
try {
  $inventoryService = app(\App\Services\InventoryService::class);

  $items = [
    [
      'product_id' => $productId,
      'quantity' => 200
    ]
  ];

  $transaction = $inventoryService->importStock(1, $items, "Test import via service");

  echo "✅ InventoryService works: Transaction ID {$transaction->id}\n";

  // Kiểm tra stock sau khi import
  $stockAfter = ProductBranch::where('product_id', $productId)
    ->where('branch_id', 1)
    ->value('stock_quantity') ?? 0;

  echo "📦 Stock after service import: {$stockAfter}\n";
  echo "📈 Stock increase: " . ($stockAfter - $stockBefore) . "\n\n";
} catch (Exception $e) {
  echo "❌ InventoryService failed: " . $e->getMessage() . "\n";
  echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
  exit;
}

// 3. Test API endpoint simulation
echo "🌐 Testing API endpoint simulation:\n";

try {
  // Simulate request
  $requestData = [
    'branch_id' => 1,
    'items' => [
      [
        'product_id' => $productId,
        'quantity' => 150
      ]
    ],
    'note' => 'Test API import'
  ];

  echo "📤 Request data:\n";
  echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

  // Get current stock
  $stockBefore2 = ProductBranch::where('product_id', $productId)
    ->where('branch_id', 1)
    ->value('stock_quantity') ?? 0;

  // Call service again
  $transaction2 = $inventoryService->importStock(
    $requestData['branch_id'],
    $requestData['items'],
    $requestData['note']
  );

  $stockAfter2 = ProductBranch::where('product_id', $productId)
    ->where('branch_id', 1)
    ->value('stock_quantity') ?? 0;

  echo "✅ API simulation successful!\n";
  echo "📦 Stock before: {$stockBefore2}\n";
  echo "📦 Stock after: {$stockAfter2}\n";
  echo "📈 Stock increase: " . ($stockAfter2 - $stockBefore2) . "\n\n";

  echo "📋 Transaction details:\n";
  echo "   - ID: {$transaction2->id}\n";
  echo "   - Type: {$transaction2->transaction_type->value}\n";
  echo "   - Note: {$transaction2->note}\n";
  echo "   - Branch ID: {$transaction2->branch_id}\n";
} catch (Exception $e) {
  echo "❌ API simulation failed: " . $e->getMessage() . "\n";
  echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
  echo "🔍 Trace:\n" . $e->getTraceAsString() . "\n\n";
}

// 4. Test với cURL thực tế (nếu server đang chạy)
echo str_repeat("=", 50) . "\n";
echo "🌐 Testing real API endpoint with cURL:\n";
echo str_repeat("=", 50) . "\n\n";

$apiUrl = "http://localhost/karinox-fnb/public/api/admin/inventory/import";
$postData = json_encode([
  'branch_id' => 1,
  'items' => [
    [
      'product_id' => $productId,
      'quantity' => 100
    ]
  ],
  'note' => 'Test via cURL'
]);

echo "📤 Testing API: POST {$apiUrl}\n";
echo "📦 Data: {$postData}\n\n";

// Test API endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Accept: application/json',
  'karinox-app-id: karinox-app-admin'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📥 API Response:\n";
echo "   - HTTP Code: {$httpCode}\n";
echo "   - Response: {$response}\n\n";

if ($httpCode === 200 || $httpCode === 201) {
  echo "✅ API test successful!\n";
} else {
  echo "❌ API test failed!\n";

  // Debug response
  $responseData = json_decode($response, true);
  if ($responseData) {
    if (isset($responseData['message'])) {
      echo "🔍 Error message: {$responseData['message']}\n";
    }
    if (isset($responseData['errors'])) {
      echo "🔍 Validation errors: " . json_encode($responseData['errors'], JSON_PRETTY_PRINT) . "\n";
    }
  }
}

echo "\n📊 Final inventory status:\n";
$finalStock = ProductBranch::where('product_id', $productId)
  ->where('branch_id', 1)
  ->value('stock_quantity') ?? 0;
echo "   - Product: {$product->name}\n";
echo "   - Final stock: {$finalStock}\n";
echo "   - Total increase from start: " . ($finalStock - $stockBefore) . "\n";

echo "\n✅ Inventory import test completed!\n";
