<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchUserFactory extends Factory
{
  /**
   * Define the model's default state.
   */
  public function definition(): array
  {
    return [
      'branch_id' => Branch::inRandomOrder()->first()->id ?? Branch::factory(),
      'user_id' => User::inRandomOrder()->first()->id ?? User::factory()
    ];
  }
}
