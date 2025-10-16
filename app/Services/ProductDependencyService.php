<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStockDependency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductDependencyService
{
  /**
   * Cập nhật dependencies cho một sản phẩm
   */
  public function updateDependencies(Product $product): void
  {
    DB::transaction(function () use ($product) {
      // 1. Validate không có circular dependency
      $this->validateNoCircularDependency($product);

      // 2. Xóa dependencies cũ
      ProductStockDependency::where('source_product_id', $product->id)->delete();

      // 3. Tính toán dependencies mới
      $dependencies = $this->calculateFlatDependencies($product);

      // 4. Insert dependencies mới
      if (!empty($dependencies)) {
        ProductStockDependency::insert($dependencies);
      }

      // 5. Clear cache
      Cache::forget("product_dependencies_{$product->id}");

      // 6. Update parent dependencies nếu cần
      $this->updateParentDependencies($product);
    });
  }

  /**
   * Tính toán flat dependencies cho một sản phẩm
   */
  private function calculateFlatDependencies(Product $product): array
  {
    $result = [];

    // 🔹 Kiểm tra nếu là goods/ingredient → thêm self-reference với quantity = 1
    if (in_array($product->product_type->value, ['goods', 'ingredient'])) {
      $result[$product->id . '_' . $product->id] = [
        'source_product_id' => $product->id,
        'target_product_id' => $product->id,
        'quantity' => 1, // 1 đơn vị = 1 đơn vị
        'created_at' => now(),
        'updated_at' => now(),
      ];
    } else {
      // 🔹 Sản phẩm phức tạp → expand theo formulas
      $this->expandProduct($product, $product->id, 1, $result);
    }

    return array_values($result);
  }

  /**
   * Expand sản phẩm thành các dependencies
   * @param int $multiplier - Số lượng nhân lên (đơn vị nguyên)
   */
  private function expandProduct(Product $product, int $sourceId, int $multiplier, array &$result, array $visited = []): void
  {
    // Prevent infinite recursion
    if (in_array($product->id, $visited)) {
      Log::warning("Circular dependency detected", [
        'product_id' => $product->id,
        'source_id' => $sourceId,
        'visited' => $visited
      ]);
      return;
    }

    $visited[] = $product->id;

    // Load formulas if not loaded
    $product->loadMissing('formulas.ingredient');

    foreach ($product->formulas as $formula) {
      $component = $formula->ingredient;
      $newQuantity = $multiplier * $formula->quantity; // quantity trong formula đã là số nguyên (gram, ml, cái...)

      if ($this->isPhysicalStockProduct($component)) {
        // Sản phẩm vật lý - thêm vào result
        $key = $sourceId . '_' . $component->id;

        if (isset($result[$key])) {
          // Cộng dồn nếu đã có
          $result[$key]['quantity'] += $newQuantity;
        } else {
          $result[$key] = [
            'source_product_id' => $sourceId,
            'target_product_id' => $component->id,
            'quantity' => (int)$newQuantity, // Cast to integer
            'created_at' => now(),
            'updated_at' => now(),
          ];
        }
      } else {
        // Sản phẩm phức hợp - expand tiếp
        $this->expandProduct($component, $sourceId, (int)$newQuantity, $result, $visited);
      }
    }
  }

  /**
   * Kiểm tra sản phẩm có phải là sản phẩm vật lý (goods/ingredient) không
   * Note: Không kiểm tra manage_stock ở đây, sẽ kiểm tra khi deduction
   */
  private function isPhysicalStockProduct(Product $product): bool
  {
    return in_array($product->product_type->value, ['goods', 'ingredient']);
  }

  /**
   * Validate không có circular dependency
   */
  private function validateNoCircularDependency(Product $product, array $path = []): void
  {
    if (in_array($product->id, $path)) {
      throw new \Exception("Circular dependency detected: " . implode(' -> ', $path) . ' -> ' . $product->id);
    }

    $path[] = $product->id;
    $product->loadMissing('formulas.ingredient');

    foreach ($product->formulas as $formula) {
      $component = $formula->ingredient;
      if (!$this->isPhysicalStockProduct($component)) {
        $this->validateNoCircularDependency($component, $path);
      }
    }
  }

  /**
   * Update dependencies cho các sản phẩm cha
   */
  private function updateParentDependencies(Product $product): void
  {
    // 1. Update các sản phẩm có $product trong formulas (ingredients)
    $parentsViaFormulas = Product::whereHas('formulas', function ($q) use ($product) {
      $q->where('ingredient_id', $product->id);
    })->get();

    foreach ($parentsViaFormulas as $parent) {
      $this->updateDependencies($parent);
    }

    // 2. Update các sản phẩm có $product làm topping
    if ($product->is_topping) {
      $parentsViaToppings = Product::whereHas('toppings', function ($q) use ($product) {
        $q->where('topping_id', $product->id);
      })->get();

      foreach ($parentsViaToppings as $parent) {
        // Không cần update dependencies cho parent vì topping chỉ trừ riêng
        // Nhưng cần clear cache nếu có
        Cache::forget("product_dependencies_{$parent->id}");
      }
    }
  }

  /**
   * Rebuild toàn bộ dependencies cho tất cả sản phẩm
   */
  public function rebuildAllDependencies(): void
  {
    Log::info("Starting rebuild all product dependencies");

    // Xóa tất cả dependencies trước
    ProductStockDependency::truncate();

    // Rebuild cho tất cả sản phẩm (bao gồm goods/ingredient với self-reference)
    $count = 0;
    Product::with(['formulas.ingredient'])
      ->chunk(100, function ($products) use (&$count) {
        $batchDependencies = [];

        foreach ($products as $product) {
          try {
            $dependencies = $this->calculateFlatDependencies($product);
            if (!empty($dependencies)) {
              $batchDependencies = array_merge($batchDependencies, $dependencies);
            }
            $count++;
          } catch (\Exception $e) {
            Log::error("Error rebuilding dependencies for product {$product->id}: " . $e->getMessage());
          }
        }

        // Insert batch dependencies
        if (!empty($batchDependencies)) {
          ProductStockDependency::insert($batchDependencies);
        }
      });

    Log::info("Completed rebuild all product dependencies for {$count} products");
  }

  /**
   * Get dependencies cho một sản phẩm (có cache)
   */
  public function getDependencies(int $productId): \Illuminate\Support\Collection
  {
    return Cache::remember("product_dependencies_{$productId}", 3600, function () use ($productId) {
      return ProductStockDependency::where('source_product_id', $productId)->get();
    });
  }
}
