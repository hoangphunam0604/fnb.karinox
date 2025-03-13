<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use App\Models\ProductFormula;
use App\Models\ProductTopping;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductImportService
{
  protected array $toppingList = [];
  protected array $formulaList = [];

  public function importFromExcel($filePath)
  {
    DB::beginTransaction();

    try {
      $data = Excel::toArray([], $filePath)[0];

      foreach ($data as $index => $row) {
        if ($index === 0) continue; // Bỏ qua dòng tiêu đề

        $this->processProductRow($row);
      }

      // Xử lý toppings và thành phần sau khi tất cả sản phẩm đã được nhập
      $this->processToppings();
      $this->processFormulas();

      DB::commit();
      return ['success' => true, 'message' => 'Import thành công!'];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Import sản phẩm lỗi: ' . $e->getMessage());
      return ['success' => false, 'message' => 'Import thất bại! Lỗi: ' . $e->getMessage()];
    }
  }

  private function processProductRow($row)
  {
    $code = trim($row[4]); // Mã hàng
    $name = trim($row[5]); // Tên hàng hóa
    $categoryName = trim($row[2]); // Nhóm hàng (danh mục)
    $price = floatval($row[7]); // Giá bán
    $stockQuantity = intval($row[9]); // Tồn kho
    $allowsSale = intval($row[16]); // Đang kinh doanh
    $isTopping = intval($row[19]); // Là món thêm
    $ingredientString = trim($row[21]); // Hàng thành phần (VD: "SP000170:10")

    // Tạo danh mục nếu chưa có
    $category = Category::firstOrCreate(['name' => $categoryName]);

    // Kiểm tra sản phẩm đã tồn tại chưa
    $product = Product::updateOrCreate(
      ['code' => $code],
      [
        'name' => $name,
        'category_id' => $category->id,
        'price' => $price,
        'allows_sale' => $allowsSale,
        'is_topping' => $isTopping
      ]
    );

    // Xử lý tồn kho
    ProductBranch::updateOrCreate(
      ['product_id' => $product->id, 'branch_id' => 1], // Giả sử branch_id = 1
      ['stock_quantity' => $stockQuantity]
    );

    // Lưu topping để xử lý sau
    if ($isTopping) {
      $this->toppingList[$product->id] = [];
    }

    // Lưu thành phần để xử lý sau
    if (!empty($ingredientString)) {
      foreach (explode(',', $ingredientString) as $ingredient) {
        [$ingredientCode, $quantity] = explode(':', $ingredient);
        $this->formulaList[$product->id][] = [
          'ingredient_code' => trim($ingredientCode),
          'quantity' => intval($quantity)
        ];
      }
    }
  }

  private function processToppings()
  {
    foreach ($this->toppingList as $productId => $toppings) {
      foreach ($toppings as $toppingCode) {
        $toppingProduct = Product::where('code', $toppingCode)->first();
        if ($toppingProduct) {
          ProductTopping::firstOrCreate([
            'product_id' => $productId,
            'topping_id' => $toppingProduct->id
          ]);
        }
      }
    }
  }

  private function processFormulas()
  {
    foreach ($this->formulaList as $productId => $ingredients) {
      foreach ($ingredients as $ingredientData) {
        $ingredientProduct = Product::where('code', $ingredientData['ingredient_code'])->first();
        if ($ingredientProduct) {
          ProductFormula::firstOrCreate([
            'product_id' => $productId,
            'ingredient_id' => $ingredientProduct->id,
            'quantity' => $ingredientData['quantity']
          ]);
        }
      }
    }
  }
}
