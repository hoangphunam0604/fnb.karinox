<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
  protected $model = Branch::class;

  public function definition(): array
  {
    return [
      'name' => $this->faker->company . ' - Chi nhánh ' . $this->faker->numberBetween(1, 10),
      'address' => $this->faker->address,
      'phone_number' => $this->faker->phoneNumber,
      'email' => $this->faker->unique()->safeEmail,
      'status' => \App\Enums\CommonStatus::ACTIVE,
      'sort_order' => $this->faker->numberBetween(1, 100),
    ];
  }
}
