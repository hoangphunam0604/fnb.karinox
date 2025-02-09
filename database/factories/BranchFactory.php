<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Branch;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{

  protected $model = Branch::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'name' => $this->faker->company, // Tạo tên chi nhánh giả
      'address' => $this->faker->address, // Tạo địa chỉ giả
      'phone_number' => $this->faker->unique()->phoneNumber, // Tạo số điện thoại giả
      'email' => $this->faker->unique()->safeEmail, // Email giả
      'status' => $this->faker->randomElement(['active', 'inactive']), // Trạng thái ngẫu nhiên
    ];
  }
}
