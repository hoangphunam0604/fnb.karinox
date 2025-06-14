<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductAttribute;
use App\Models\ProductFormula;
use App\Models\ProductTopping;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProductService extends BaseService
{
  protected array $with = ['category'];
  protected array $withCount = [];

  protected function model(): Model
  {
    return new Product();
  }
  /**
   * Tạo sản phẩm
   */
  public function create(array $data): Model
  {
    return $this->saveProduct($data);
  }

  /**
   * Cập nhật sản phẩm
   */
  public function update($productId, array $data): Model
  {
    return $this->saveProduct($data, $productId);
  }

  /**
   * Tạo hoặc cập nhật sản phẩm
   */
  private function saveProduct(array $data, $productId = null)
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

  protected function applySearch($query, array $params)
  {
    if (!empty($params['keyword'])) {
      $query->where('name', 'LIKE', '%' . $params['keyword'] . '%')
        ->orWhere('code', 'LIKE', '%' . $params['keyword'] . '%');
    }
    return $query;
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

  /**
   * Lấy danh sách sản phẩm theo chi nhánh, nhóm theo danh mục.
   *
   * @param int $branchId
   * @return array
   */
  public function getProductsByBranch(int $branchId)
  {
    // Lấy danh sách sản phẩm thuộc chi nhánh được chọn
    $products = Product::select('products.*', 'categories.name as category_name')
      ->with('toppings.toppingProduct')
      ->join('product_branches', 'products.id', '=', 'product_branches.product_id')
      ->join('categories', 'products.category_id', '=', 'categories.id')
      ->where('product_branches.branch_id', $branchId)
      ->where('products.allows_sale', true) // Chỉ lấy sản phẩm đang kinh doanh
      ->orderBy('categories.name')
      ->orderBy('products.name')
      ->get();

    // Nhóm sản phẩm theo danh mục
    $groupedProducts = $products->groupBy('category_name')->map(function ($items, $categoryName) {
      return [
        'category' => $categoryName,
        'products' => $items->map(function ($product) {
          return $product;
        }),
      ];
    })->values();

    return $groupedProducts;
  }
}
