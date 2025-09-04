<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductAttribute;
use App\Models\ProductFormula;
use App\Models\ProductTopping;
use Illuminate\Support\Arr;
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
  public function create(array $data): Product
  {
    return $this->saveProduct($data);
  }

  /**
   * Cập nhật sản phẩm
   */
  public function update($productId, array $data)
  {
    return $this->saveProduct($data, $productId);
  }

  public function manufacturingAutocomplete($params, $limit = 50)
  {
    $query = $this->getQueryBuilder();
    $query = $this->applySearch($query, $params);
    return $query->orderBy('name', 'asc')->limit($limit)->get();
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
      $defaults = [
        'product_type'   => 'goods',
        'allows_sale'    => true,
        'is_reward_point' => false,
        'is_topping'     => false,
        'print_label'    => false,
        'print_kitchen'  => false,
        'product_group'  => 1,
      ];
      // lấy danh sách field cho phép fill
      $fields = $product->getFillable();
      // chỉ lấy data liên quan đến product
      $input = Arr::only($data, $fields);
      // gộp lại: ưu tiên $data, sau đó $product cũ, cuối cùng là default
      $merged = array_merge($defaults, $product->only($fields), $input);
      $product->fill($merged);

      $product->save();
      // Đồng bộ dữ liệu liên quan
      $this->syncBranches($product, $data['branches'] ?? []);
      $this->syncAttributes($product->id, $data['attributes'] ?? []);
      $this->syncFormulas($product->id, $data['formulas'] ?? []);
      $this->syncToppings($product->id, $data['toppings'] ?? []);

      return $product;
    });
  }

  protected function applySearch($query, array $params)
  {
    if (!empty($params['keyword'])):
      $keyword = $params['keyword'];
      $query->where(function ($subQuery) use ($keyword) {
        $subQuery->where('name', 'LIKE', '%' . $keyword . '%')
          ->orWhere('code', 'LIKE', '%' . $keyword . '%');
      });
    endif;
    if (!empty($params['category_id']))
      $query->where('category_id', $params['category_id']);

    if (!empty($params['is_topping']))
      $query->where('is_topping', $params['is_topping']);

    // excludes có thể là array hoặc chuỗi "1,2,3"
    $excludes = $params['excludes'] ?? [];
    if (is_string($excludes)) {
      $excludes = array_filter(array_map('intval', explode(',', $excludes)));
    } elseif (is_array($excludes)) {
      $excludes = array_filter(array_map('intval', $excludes));
    }

    if (!empty($excludes)) {
      $query->whereNotIn('id', $excludes);
    }
    return $query;
  }

  /**
   * Đồng bộ chi nhánh sản phẩm
   */
  private function syncBranches(Product $product, array $branches)
  {
    $wantMap = [];
    foreach ($branches as $row) {
      $branch_id = (int)($row['branch_id'] ?? 0);
      if ($branch_id <= 0) continue;
      $wantMap[$branch_id] = (bool)($row['is_selling'] ?? false);
    }
    $allBranchIds = $product->branches()->pluck('branches.id')->all(); // hiện có trên pivot
    $incomingIds  = array_keys($wantMap);

    // Tạo mới các pivot CÒN THIẾU (không xoá cái nào)
    $toAttach = array_diff($incomingIds, $allBranchIds);
    if (!empty($toAttach)) {
      $attachData = [];
      foreach ($toAttach as $bid) {
        $attachData[$bid] = ['is_selling' => $wantMap[$bid]];
      }
      $product->branches()->attach($attachData);
    }
    // Cập nhật các pivot đã có
    $toUpdate = array_intersect($incomingIds, $allBranchIds);
    foreach ($toUpdate as $bid) {
      $product->branches()->updateExistingPivot($bid, [
        'is_selling' => $wantMap[$bid],
      ]);
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
