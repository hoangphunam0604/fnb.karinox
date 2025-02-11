<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductAttribute;
use App\Models\ProductFormula;
use App\Models\ProductTopping;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProductService
{

  /**
   * Tạo sản phẩm
   */
  public function createProduct(array $data)
  {
    return $this->saveProduct($data);
  }

  /**
   * Cập nhật sản phẩm
   */
  public function updateProduct($productId, array $data)
  {
    return $this->saveProduct($data, $productId);
  }

  /**
   * Tạo hoặc cập nhật sản phẩm
   */
  public function saveProduct(array $data, $productId = null)
  {
    return DB::transaction(function () use ($data, $productId) {
      $product = $productId
        ? Product::findOrFail($productId)
        : new Product();

      $product->fill([
        'code' => $data['code'] ?? $product->code,
        'name' => $data['name'] ?? $product->name,
        'category_id' => $data['category_id'] ?? $product->category_id,
        'product_type' => $data['product_type'] ?? $product->product_type ?? 'goods',
        'allows_sale' => $data['allows_sale'] ?? $product->allows_sale ?? true,
        'is_reward_point' => $data['is_reward_point'] ?? $product->is_reward_point ?? false,
        'is_topping' => $data['is_topping'] ?? $product->is_topping ?? false,
        'product_group' => $data['product_group'] ?? $product->product_group ?? 1,
      ]);

      $product->save();

      // Đồng bộ dữ liệu liên quan
      $this->syncBranches($product->id, $data['branches'] ?? []);
      $this->syncAttributes($product->id, $data['attributes'] ?? []);
      $this->syncFormulas($product->id, $data['formulas'] ?? []);
      $this->syncToppings($product->id, $data['toppings'] ?? []);

      return $product;
    });
  }

  /**
   * Xóa sản phẩm
   */
  public function deleteProduct($productId)
  {
    return Product::findOrFail($productId)->delete();
  }

  /**
   * Tìm kiếm sản phẩm theo tên hoặc mã (code)
   */
  public function findProduct($keyword)
  {
    return Product::where('name', 'LIKE', "%{$keyword}%")
      ->orWhere('code', 'LIKE', "%{$keyword}%")
      ->first();
  }

  /**
   * Lấy danh sách tất cả sản phẩm (phân trang)
   */
  public function getProducts($perPage = 10)
  {
    return Product::orderBy('created_at', 'desc')->paginate($perPage);
  }

  /**
   * Đồng bộ chi nhánh sản phẩm
   */
  private function syncBranches($productId, array $branches)
  {
    ProductBranch::where('product_id', $productId)->delete();

    if (!empty($branches)) {
      $branchData = array_map(fn($branch) => [
        'product_id' => $productId,
        'branch_id' => $branch['branch_id'],
        'stock_quantity' => $branch['stock_quantity'] ?? 0,
      ], $branches);

      ProductBranch::insert($branchData);
    }
  }

  /**
   * Đồng bộ thuộc tính sản phẩm
   */
  private function syncAttributes($productId, array $attributes)
  {
    ProductAttribute::where('product_id', $productId)->delete();

    if (!empty($attributes)) {
      $attributeData = array_map(fn($attr) => [
        'product_id' => $productId,
        'attribute_id' => $attr['attribute_id'],
        'value' => $attr['value'] ?? null,
      ], $attributes);

      ProductAttribute::insert($attributeData);
    }
  }

  /**
   * Đồng bộ công thức (thành phần) sản phẩm
   */
  private function syncFormulas($productId, array $formulas)
  {
    ProductFormula::where('product_id', $productId)->delete();

    if (!empty($formulas)) {
      $formulaData = array_map(fn($formula) => [
        'product_id' => $productId,
        'ingredient_id' => $formula['ingredient_id'],
        'quantity' => $formula['quantity'],
      ], $formulas);

      ProductFormula::insert($formulaData);
    }
  }

  /**
   * Đồng bộ topping sản phẩm (chỉ nhận các sản phẩm có is_topping = true)
   */
  private function syncToppings($productId, array $toppingIds)
  {
    ProductTopping::where('product_id', $productId)->delete();

    if (!empty($toppingIds)) {
      // Chỉ lấy những sản phẩm có is_topping = true
      $validToppings = Product::whereIn('id', $toppingIds)
        ->where('is_topping', true)
        ->pluck('id')
        ->toArray();

      if (!empty($validToppings)) {
        $toppingData = array_map(fn($toppingId) => [
          'product_id' => $productId,
          'topping_id' => $toppingId,
        ], $validToppings);

        ProductTopping::insert($toppingData);
      }
    }
  }
}
