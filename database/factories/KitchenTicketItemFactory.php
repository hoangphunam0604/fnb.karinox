<?php

namespace Database\Factories;

use App\Models\KitchenTicket;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KitchenTicketItem>
 */
class KitchenTicketItemFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'kitchen_ticket_id' => KitchenTicket::factory(), // Tạo vé bếp giả
      'order_item_id' => OrderItem::factory(), // Tạo sản phẩm trong đơn hàng giả
      'product_id' => Product::factory(), // Tạo sản phẩm giả
      'quantity' => $this->faker->numberBetween(1, 5),
      'status' => $this->faker->randomElement(['waiting', 'processing', 'completed', 'canceled']),
      'note' => $this->faker->optional()->sentence(), // Topping / combo
    ];
  }
}
