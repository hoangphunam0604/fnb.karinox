<?php

namespace Database\Factories;

use App\Models\ProductBranch;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBranchFactory extends Factory
{
  protected $model = ProductBranch::class;

  public function definition()
  {
    return [
      'product_id' => Product::factory(), // Tạo sản phẩm giả lập
      'branch_id' => Branch::factory(), // Tạo chi nhánh giả lập
      'stock_quantity' => $this->faker->numberBetween(0, 100), // Số lượng tồn kho ngẫu nhiên
    ];
  }
}
