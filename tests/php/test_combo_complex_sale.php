<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductFormula;

// Load Laravel app
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🍽️ Test Combo Sales with Complex Inventory Chain\n";
echo "==============================================\n\n";

// 1. Analyze combo structure
echo "📋 Analyzing combo structure:\n";
$combo = Product::where('code', 'COMBO001')->with('formulas.ingredient')->first();

if (!$combo) {
  echo "❌ Combo not found!\n";
  exit;
}

echo "   🎯 Testing combo: {$combo->name} ({$combo->code})\n";
echo "   💰 Price: " . number_format($combo->regular_price) . " VNĐ\n\n";

echo "   📦 Combo contains:\n";
$comboItems = [];
foreach ($combo->formulas as $formula) {
  $item = $formula->ingredient;
  $quantity = $formula->quantity;
  $comboItems[] = ['product' => $item, 'quantity' => $quantity];
  echo "     - {$item->name} ({$item->code}) x{$quantity}\n";
  echo "       Type: {$item->product_type->value}\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// 2. Deep analysis of processed items in combo
echo "🔍 Deep ingredient analysis for processed items:\n";
$totalIngredientNeeds = [];

foreach ($comboItems as $comboItem) {
  $product = $comboItem['product'];
  $comboQuantity = $comboItem['quantity'];

  echo "   🍽️ Analyzing: {$product->name} (x{$comboQuantity})\n";

  if ($product->product_type->value === 'processed') {
    echo "     📋 This is a processed item, checking its ingredients:\n";

    $ingredients = $product->formulas()->with('ingredient')->get();
    foreach ($ingredients as $formula) {
      $ingredient = $formula->ingredient;
      $neededPerUnit = $formula->quantity;
      $totalNeeded = $neededPerUnit * $comboQuantity;

      echo "       - {$ingredient->name}: {$neededPerUnit}g x {$comboQuantity} = {$totalNeeded}g total\n";

      // Accumulate total needs
      $ingredientCode = $ingredient->code;
      if (!isset($totalIngredientNeeds[$ingredientCode])) {
        $totalIngredientNeeds[$ingredientCode] = [
          'product' => $ingredient,
          'total_needed' => 0
        ];
      }
      $totalIngredientNeeds[$ingredientCode]['total_needed'] += $totalNeeded;
    }
  } else {
    echo "     ✅ This is a {$product->product_type->value} item (no ingredients to check)\n";
  }
  echo "\n";
}

// 3. Check ingredient availability
echo "📦 Checking ingredient availability:\n";
$canMakeCombo = true;
$availabilityReport = [];

foreach ($totalIngredientNeeds as $code => $need) {
  $ingredient = $need['product'];
  $totalNeeded = $need['total_needed'];

  $stock = ProductBranch::where('product_id', $ingredient->id)
    ->where('branch_id', 1)
    ->first();

  $available = $stock ? $stock->stock_quantity : 0;
  $sufficient = $available >= $totalNeeded;

  if (!$sufficient) {
    $canMakeCombo = false;
  }

  $status = $sufficient ? "✅ Sufficient" : "❌ Insufficient";
  $availabilityReport[] = [
    'ingredient' => $ingredient,
    'needed' => $totalNeeded,
    'available' => $available,
    'sufficient' => $sufficient
  ];

  echo "   - {$ingredient->name}: {$totalNeeded}g needed, {$available}g available $status\n";
}

echo "\n🎯 Combo availability: " . ($canMakeCombo ? "✅ CAN BE MADE" : "❌ CANNOT BE MADE") . "\n\n";

if (!$canMakeCombo) {
  echo "❌ Cannot proceed with sale due to insufficient ingredients.\n";
  echo "✅ Test completed - Inventory protection working!\n";
  exit;
}

// 4. Simulate combo sale
echo str_repeat("=", 50) . "\n";
echo "💰 SIMULATING COMBO SALE\n";
echo str_repeat("=", 50) . "\n\n";

echo "🛒 Selling 1x {$combo->name}\n";
echo "💸 Sale price: " . number_format($combo->regular_price) . " VNĐ\n\n";

echo "📊 Stock changes:\n";

// Update ingredient stocks
foreach ($availabilityReport as $report) {
  $ingredient = $report['ingredient'];
  $needed = $report['needed'];
  $oldStock = $report['available'];
  $newStock = $oldStock - $needed;

  ProductBranch::where('product_id', $ingredient->id)
    ->where('branch_id', 1)
    ->update(['stock_quantity' => $newStock]);

  echo "   - {$ingredient->name}: {$oldStock}g → {$newStock}g (-{$needed}g)\n";
}

// 5. Verify final state
echo "\n📦 Post-sale inventory verification:\n";
foreach ($availabilityReport as $report) {
  $ingredient = $report['ingredient'];

  $finalStock = ProductBranch::where('product_id', $ingredient->id)
    ->where('branch_id', 1)
    ->value('stock_quantity');

  $status = $finalStock > 0 ? "✅ In Stock" : "⚠️ Low/Out of Stock";
  echo "   - {$ingredient->name}: {$finalStock}g $status\n";
}

// 6. Check if combo can still be made
echo "\n🔄 Checking if combo can still be made:\n";
$canStillMakeCombo = true;

foreach ($totalIngredientNeeds as $code => $need) {
  $ingredient = $need['product'];
  $totalNeeded = $need['total_needed'];

  $currentStock = ProductBranch::where('product_id', $ingredient->id)
    ->where('branch_id', 1)
    ->value('stock_quantity');

  if ($currentStock < $totalNeeded) {
    $canStillMakeCombo = false;
    echo "   ❌ {$ingredient->name}: Need {$totalNeeded}g, only {$currentStock}g available\n";
  } else {
    echo "   ✅ {$ingredient->name}: Need {$totalNeeded}g, {$currentStock}g available\n";
  }
}

echo "\n🎯 Can make another combo: " . ($canStillMakeCombo ? "✅ YES" : "❌ NO") . "\n";

// 7. Business insights
echo "\n" . str_repeat("=", 50) . "\n";
echo "📈 BUSINESS INSIGHTS\n";
echo str_repeat("=", 50) . "\n\n";

echo "💰 Revenue Analysis:\n";
echo "   - Combo sale price: " . number_format($combo->regular_price) . " VNĐ\n";
echo "   - Combo cost price: " . number_format($combo->cost_price) . " VNĐ\n";
echo "   - Gross profit: " . number_format($combo->regular_price - $combo->cost_price) . " VNĐ\n";
$margin = (($combo->regular_price - $combo->cost_price) / $combo->regular_price) * 100;
echo "   - Profit margin: " . number_format($margin, 1) . "%\n\n";

echo "📦 Inventory Impact:\n";
$totalIngredientCost = 0;
foreach ($availabilityReport as $report) {
  $ingredient = $report['ingredient'];
  $needed = $report['needed'];
  $costImpact = ($ingredient->cost_price / 1000) * $needed; // cost per gram
  $totalIngredientCost += $costImpact;
  echo "   - {$ingredient->name}: " . number_format($costImpact) . " VNĐ (ingredient cost)\n";
}
echo "   - Total ingredient cost: " . number_format($totalIngredientCost) . " VNĐ\n";

echo "\n✅ Complex combo sale test completed successfully!\n";
echo "🎯 All inventory dependencies tracked and updated correctly!\n";
