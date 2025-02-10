<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Area>
 */
class OrderFactory extends Factory
{
  protected $model = Order::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'customer_id' => Customer::factory(),
      'branch_id' => Branch::factory(),
      'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
      'note' => $this->faker->sentence(),
    ];
  }
}
