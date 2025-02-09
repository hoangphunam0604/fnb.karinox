<?php

use Tests\TestCase;
use App\Models\ProductBranch;
use App\Models\Product;
use App\Models\Branch;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryServiceTest extends TestCase
{
  use RefreshDatabase; // Tạo lại database trước mỗi tes0t

  public function test_sale_stock_updates_inventory()
  {
    // Tạo dữ liệu giả
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $productBranch = ProductBranch::factory()->create([
      'product_id' => $product->id,
      'branch_id' => $branch->id,
      'stock_quantity' => 50,
    ]);

    // Khởi tạo service
    $service = new InventoryService();

    // Gọi phương thức saleStock
    $service->saleStock($branch->id, [
      ['product_id' => $product->id, 'quantity' => 10]
    ]);

    // Kiểm tra tồn kho đã cập nhật đúng
    $this->assertDatabaseHas('product_branches', [
      'product_id' => $product->id,
      'branch_id' => $branch->id,
      'stock_quantity' => 40, // 50 - 10 = 40
    ]);
  }
}
