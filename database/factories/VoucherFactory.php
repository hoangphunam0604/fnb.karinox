<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Voucher;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'code' => strtoupper(Str::random(8)),
      'discount_type' => $this->faker->randomElement(['percentage', 'fixed']),
      'discount_value' => $this->faker->numberBetween(5, 50),
      'min_order_value' => $this->faker->numberBetween(50, 500),
      'max_discount' => $this->faker->numberBetween(10, 100),
      'usage_limit' => $this->faker->numberBetween(10, 500),
      'used_count' => 0,
      'expires_at' => now()->addDays($this->faker->numberBetween(1, 90)),
      'status' => $this->faker->randomElement(['active', 'inactive']),
    ];
  }
}
