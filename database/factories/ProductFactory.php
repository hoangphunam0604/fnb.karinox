<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
  protected $model = Product::class;

  public function definition()
  {
    return [
      'code' => strtoupper(Str::random(8)), // Mã sản phẩm ngẫu nhiên (VD: AB12CD34)
      'name' => $this->faker->word . ' ' . $this->faker->randomElement(['Cà phê', 'Trà', 'Nước ép', 'Sinh tố']), // Tạo tên ngẫu nhiên
      'category_id' => Category::factory(), // Tạo danh mục ngẫu nhiên
      'product_type' => ProductType::fake()->value,
      'allows_sale' => $this->faker->boolean(90), // 90% sản phẩm có thể bán
      'is_reward_point' => $this->faker->boolean(50), // 50% sản phẩm tích điểm
      'is_topping' => $this->faker->boolean(10), // 10% sản phẩm có thể là topping
      'product_group' => $this->faker->randomDigit, // Nhóm sản phẩm (0-9)
    ];
  }

  public function asTopping()
  {
    return $this->state(fn() => ['is_topping' => true]);
  }

  public function withFormulas($count = 2)
  {
    return $this->afterCreating(function (Product $product) use ($count) {
      $ingredients = Product::factory()->count($count)->create();
      foreach ($ingredients as $ingredient) {
        $product->formulas()->create([
          'ingredient_id' => $ingredient->id,
          'quantity' => rand(1, 3),
        ]);
      }
    });
  }

  public function withToppings($count = 2)
  {
    return $this->afterCreating(function (Product $product) use ($count) {
      $toppings = Product::factory()->asTopping()->count($count)->create();
      foreach ($toppings as $topping) {
        $product->toppings()->create(['topping_id' => $topping->id]);
      }
    });
  }
}
