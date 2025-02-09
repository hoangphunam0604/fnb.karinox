<?php

namespace Database\Factories;

use App\Models\ProductFormula;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFormulaFactory extends Factory
{
  protected $model = ProductFormula::class;

  public function definition()
  {
    return [
      'product_id' => Product::factory(), // Sản phẩm chính
      'ingredient_id' => Product::factory(), // Thành phần (có thể là một sản phẩm khác)
      'quantity' => $this->faker->randomFloat(2, 0.1, 10), // Số lượng nguyên liệu cần
    ];
  }
}
