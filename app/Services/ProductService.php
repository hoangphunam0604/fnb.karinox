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
        'menu_id'      => null,
        'allows_sale'    => false,
        'is_reward_point' => false,
        'print_label'    => false,
        'print_kitchen'  => false
      ];
      // lấy danh sách field cho phép fill
      $fields = $product->getFillable();
      // chỉ lấy data liên quan đến product
      $input = Arr::only($data, $fields);
      // gộp lại: ưu tiên $data, sau đó $product cũ, cuối cùng là default
      $merged = array_merge($defaults, $productId ? $product->only($fields) : [], $input);
      $product->fill($merged);
      $product->save();

      // Đồng bộ dữ liệu liên quan
      $this->syncBranches($product, $data['branches'] ?? []);
      /*  $this->syncAttributes($product->id, $data['attributes'] ?? []);
      $this->syncFormulas($product->id, $data['formulas'] ?? []);
      $this->syncToppings($product->id, $data['toppings'] ?? []); */

      return $product;
    });
  }

  protected function applySearch($query, array $params)
  {
    $query = parent::applySearch($query, $params);
    if (!empty($params['keyword'])):
      $keyword = $params['keyword'];
      $query->where(function ($subQuery) use ($keyword) {
        $subQuery->where('name', 'LIKE', '%' . $keyword . '%')
          ->orWhere('code', 'LIKE', '%' . $keyword . '%');
      });
    endif;
    if (!empty($params['menu_id']))
      $query->where('menu_id', $params['menu_id']);

    /* if (!empty($params['product_type'])):
      $productType = $params['product_type'];
      if (is_array($productType)) {
        $query->whereIn('product_type', $productType);
      } else {
        $query->where('product_type', $productType);
      }
    endif; */

    if (!empty($params['allows_sale']) && $params['allows_sale'] != null):
      $allowsSale = filter_var($params['allows_sale'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      $query->where('allows_sale', $allowsSale);
    endif;
    if (!empty($params['is_topping'])):
      $isTopping = filter_var($params['is_topping'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      $query->where('is_topping', $isTopping);
    endif;

    if (!empty($params['branch_ids']) || !empty($params['branches'])):
      $branchIds = $params['branch_ids'] ?? $params['branches'];
      if (is_array($branchIds)) {
        $query->whereHas('branches', function ($subQuery) use ($branchIds) {
          $subQuery->whereIn('branches.id', $branchIds);
        });
      } else {
        $query->whereHas('branches', function ($subQuery) use ($branchIds) {
          $subQuery->where('branches.id', $branchIds);
        });
      }
    endif;

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
      $wantMap[$branch_id] = [
        'is_selling' => (bool)($row['is_selling'] ?? false),
        'stock_quantity' => (int)($row['stock_quantity'] ?? 0)
      ];
    }
    $allBranchIds = $product->branches()->pluck('branches.id')->all(); // hiện có trên pivot
    $incomingIds  = array_keys($wantMap);

    // Tạo mới các pivot CÒN THIẾU (không xoá cái nào)
    $toAttach = array_diff($incomingIds, $allBranchIds);
    if (!empty($toAttach)) {
      $attachData = [];
      foreach ($toAttach as $bid) {
        $attachData[$bid] = [
          'is_selling' => $wantMap[$bid]['is_selling'],
          'stock_quantity' => $wantMap[$bid]['stock_quantity']
        ];
      }
      $product->branches()->attach($attachData);
    }
    // Cập nhật các pivot đã có
    $toUpdate = array_intersect($incomingIds, $allBranchIds);
    foreach ($toUpdate as $bid) {
      $product->branches()->updateExistingPivot($bid, [
        'is_selling' => $wantMap[$bid]['is_selling'],
        'stock_quantity' => $wantMap[$bid]['stock_quantity']
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
  public function syncFormulas($productId, array $formulas)
  {
    ProductFormula::where('product_id', $productId)->delete();

    if (!empty($formulas)) {
      foreach ($formulas as $formula) {
        ProductFormula::create([
          'product_id' => $productId,
          'ingredient_id' => $formula['ingredient_id'],
          'quantity' => $formula['quantity'],
        ]);
      }
    }
  }

  /**
   * Đồng bộ topping sản phẩm (chỉ nhận các sản phẩm có is_topping = true)
   */
  public function syncToppings($productId, array $toppings)
  {
    ProductTopping::where('product_id', $productId)->delete();
    $toppingIds =  array_map(fn($topping) => $topping['topping_id'], $toppings);
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
    $products = Product::select(
      'products.*',
      'menus.name as category_name'
    )
      ->with('toppings.topping')
      ->join('product_branches', 'products.id', '=', 'product_branches.product_id')
      ->join('menus', 'products.menu_id', '=', 'menus.id')
      ->where('product_branches.branch_id', $branchId)
      ->where('products.allows_sale', true) // Chỉ lấy sản phẩm đang kinh doanh
      ->orderBy('menus.name')
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
