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
   * Tạo hoặc cập nhật sản phẩm (hỗ trợ chi nhánh, thuộc tính, thành phần, topping)
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
        'product_type' => $data['product_type'] ?? $product->product_type,
        'allows_sale' => $data['allows_sale'] ?? $product->allows_sale,
        'is_reward_point' => $data['is_reward_point'] ?? $product->is_reward_point,
        'product_group' => $data['product_group'] ?? $product->product_group,
      ]);

      $product->save();

      // Đồng bộ chi nhánh, thuộc tính, thành phần, topping
      $this->syncProductRelations($product->id, $data);

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
   * Đồng bộ các dữ liệu liên quan của sản phẩm (Chi nhánh, Thuộc tính, Thành phần, Topping)
   */
  private function syncProductRelations($productId, array $data)
  {
    // Cập nhật chi nhánh sản phẩm
    if (isset($data['branches'])) {
      ProductBranch::where('product_id', $productId)->delete();
      $branches = array_map(fn($branch) => [
        'product_id' => $productId,
        'branch_id' => $branch['branch_id'],
        'stock_quantity' => $branch['stock_quantity'] ?? 0,
      ], $data['branches']);
      ProductBranch::insert($branches);
    }

    // Cập nhật thuộc tính sản phẩm
    if (isset($data['attributes'])) {
      ProductAttribute::where('product_id', $productId)->delete();
      $attributes = array_map(fn($attr) => [
        'product_id' => $productId,
        'attribute_id' => $attr['attribute_id'],
        'value' => $attr['value'] ?? null,
      ], $data['attributes']);
      ProductAttribute::insert($attributes);
    }

    // Cập nhật công thức (thành phần) sản phẩm
    if (isset($data['formulas'])) {
      ProductFormula::where('product_id', $productId)->delete();
      $formulas = array_map(fn($formula) => [
        'product_id' => $productId,
        'ingredient_id' => $formula['ingredient_id'],
        'quantity' => $formula['quantity'],
      ], $data['formulas']);
      ProductFormula::insert($formulas);
    }

    // Cập nhật topping sản phẩm
    if (isset($data['toppings'])) {
      ProductTopping::where('product_id', $productId)->delete();
      $toppings = array_map(fn($topping) => [
        'product_id' => $productId,
        'topping_id' => $topping['topping_id'],
        'quantity' => $topping['quantity'] ?? 1,
      ], $data['toppings']);
      ProductTopping::insert($toppings);
    }
  }
}
