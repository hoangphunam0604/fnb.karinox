<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
  protected $model = OrderItem::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'order_id' => Order::factory(),
      'product_id' => Product::factory(),
      'quantity' => $this->faker->numberBetween(1, 5),
      'unit_price' => $this->faker->randomElement([20000, 50000, 100000]),
      'total_price' => fn(array $attributes) => $attributes['quantity'] * $attributes['unit_price'],
      'created_at' => now(),
      'updated_at' => now(),
    ];
  }
}
