<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;

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
      'branch_id' => Branch::factory(),
      'order_code'  =>  'ORD' . now()->timestamp . mt_rand(100, 999),
      'order_status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
      'note' => $this->faker->sentence(),
    ];
  }
}
