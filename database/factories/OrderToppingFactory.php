<?php

namespace Database\Factories;

use App\Models\OrderTopping;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderTopping>
 */
class OrderToppingFactory extends Factory
{
  protected $model = OrderTopping::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'order_item_id' => OrderItem::factory(),
      'topping_id' => Product::factory()->state(['is_topping' => true]), // Chỉ lấy sản phẩm là topping
      'unit_price' => $this->faker->randomElement([5000, 10000, 15000]),
      'created_at' => now(),
      'updated_at' => now(),
    ];
  }
}
