<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use Illuminate\Support\Str;

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
      'order_code'  => function () {
        do {
          $code = 'ORDER' . Str::uuid()->toString() . now()->timestamp . mt_rand(100, 9999999);
        } while (\App\Models\Invoice::where('code', $code)->exists());
        return $code;
      },
      'order_status' => OrderStatus::fake()->value,
      'note' => $this->faker->sentence(),
    ];
  }
}
