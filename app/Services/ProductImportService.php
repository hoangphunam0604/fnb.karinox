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

  public function importFromExcel($branch_id, $filePath)
  {
    if (!file_exists($filePath)) {
      return ['success' => false, 'message' => 'Lỗi: File không tồn tại tại ' . $filePath];
    }

    DB::beginTransaction();

    try {
      $data = Excel::toArray([], $filePath)[0];

      foreach ($data as $index => $row) {
        if ($index === 0) continue; // Bỏ qua dòng tiêu đề
        $this->processProductRow($branch_id, $row);
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

  private function processProductRow($branch_id, $row)
  {
    $productTypeRaw = trim($row[0]); // Loại hàng (chuỗi gốc từ file)
    $productType = $this->convertProductType($productTypeRaw); // Chuyển đổi loại hàng

    $categoryPath = trim($row[2]); // Nhóm hàng phân cấp
    $code = trim($row[3]); // Mã hàng
    $name = trim($row[4]); // Tên hàng hóa
    $unit = trim($row[15]); // Đơn vị tính
    $imageUrls = $this->processImages(trim($row[16])); // Xử lý hình ảnh JSON
    $price = floatval($row[6]); // Giá bán
    $stockQuantity = intval($row[7]); // Tồn kho

    // ✅ Cột 18: `status` (active/inactive)
    $status = trim(strtolower($row[18])) === 'active' ? 'active' : 'inactive';

    // ✅ Cột 19: `allows_sale` (true/false)
    $allowsSale = filter_var($row[19], FILTER_VALIDATE_BOOLEAN);

    // ✅ Cột 19: `allows_sale` (true/false)
    $manageStock = filter_var($row[27], FILTER_VALIDATE_BOOLEAN);

    $isTopping = intval($row[22]); // Là món thêm
    $ingredientString = trim($row[24]); // Hàng thành phần (VD: "SP000211:1")
    $toppingString = trim($row[25]); // Món thêm (VD: "SP000208,SP000210")

    if (empty($code) || empty($name) || empty($categoryPath)) {
      throw new \Exception("Dữ liệu không hợp lệ: Mã sản phẩm, tên sản phẩm hoặc danh mục bị thiếu.");
    }

    // ✅ Xử lý danh mục phân cấp
    $category = $this->getOrCreateCategory($categoryPath);

    // ✅ Xử lý sản phẩm
    $product = Product::updateOrCreate(
      ['code' => $code],
      [
        'name' => $name,
        'category_id' => $category->id,
        'product_type' => $productType,
        'unit' => $unit,
        'price' => $price,
        'status' => $status, // 🔥 Lưu trạng thái `active/inactive`
        'allows_sale' => $allowsSale, // 🔥 Lưu trạng thái `Được bán trực tiếp`
        'is_topping' => $isTopping,
        'images' => $imageUrls, // 🔥 Lưu JSON vào DB
        'manage_stock' => $manageStock, // 🔥 Lưu thông tin Quản lý tồn kho
      ]
    );

    // ✅ Cập nhật tồn kho
    ProductBranch::updateOrInsert(
      ['product_id' => $product->id, 'branch_id' => $branch_id], // Cập nhật chi nhánh mặc định
      ['stock_quantity' => $stockQuantity]
    );

    // ✅ Lưu danh sách toppings để xử lý sau
    if (!empty($toppingString)) {
      $this->toppingList[$product->id] = explode(',', $toppingString);
    }

    // ✅ Lưu danh sách hàng thành phần để xử lý sau
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

  private function getOrCreateCategory($categoryPath)
  {
    $categoryNames = explode(">>", $categoryPath);
    $parent = null;

    foreach ($categoryNames as $name) {
      $name = trim($name);
      if (empty($name)) continue;

      $category = Category::firstOrCreate(
        ['name' => $name, 'parent_id' => $parent ? $parent->id : null]
      );

      $parent = $category;
    }

    return $parent;
  }

  private function convertProductType($productTypeRaw)
  {
    $mapping = [
      'Hàng hóa' => 'goods',
      'Hàng chế biến' => 'processed',
      'Dịch vụ' => 'service',
      'Combo' => 'combo'
    ];

    return $mapping[$productTypeRaw] ?? 'goods';
  }

  private function processImages($imageUrls)
  {
    if (empty($imageUrls)) {
      return json_encode([]);
    }

    $urls = explode(',', $imageUrls);
    return json_encode(array_map('trim', $urls));
  }

  private function processToppings()
  {
    foreach ($this->toppingList as $productId => $toppings) {
      foreach ($toppings as $toppingCode) {
        $toppingProduct = Product::where('code', $toppingCode)->first();
        if ($toppingProduct) {
          ProductTopping::updateOrInsert(
            ['product_id' => $productId, 'topping_id' => $toppingProduct->id],
            []
          );
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
          ProductFormula::updateOrInsert(
            ['product_id' => $productId, 'ingredient_id' => $ingredientProduct->id],
            ['quantity' => $ingredientData['quantity']]
          );
        }
      }
    }
  }
}
