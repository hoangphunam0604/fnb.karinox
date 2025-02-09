<?php

namespace Database\Factories;

use App\Models\ProductTopping;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductToppingFactory extends Factory
{
  protected $model = ProductTopping::class;

  public function definition()
  {
    return [
      'product_id' => Product::factory(), // Sản phẩm chính
      'topping_id' => Product::factory(), // Topping là một sản phẩm khác
      'quantity' => $this->faker->numberBetween(1, 3), // Số lượng topping (1-3)
    ];
  }
}
