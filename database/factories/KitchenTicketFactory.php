<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KitchenTicket>
 */
class KitchenTicketFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'order_id' => Order::factory(),
      'branch_id' => Branch::factory(),
      'table_id' => null, // Có thể là null nếu đơn hàng mang đi
      'status' => $this->faker->randomElement(['waiting', 'processing', 'completed']),
      'priority' => $this->faker->numberBetween(0, 5),
      'note' => $this->faker->optional()->sentence(),
      'created_by' => User::factory(),
      'updated_by' => null,
    ];
  }
}
