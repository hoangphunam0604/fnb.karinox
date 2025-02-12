<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\MembershipLevel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipLevel>
 */
class MembershipLevelFactory extends Factory
{
  protected $model = MembershipLevel::class;

  public function definition(): array
  {
    return [
      'rank' => $this->faker->unique()->numberBetween(1, 10),
      'name' => $this->faker->unique()->word,
      'min_spent' => $this->faker->numberBetween(0, 50000),
      'max_spent' => $this->faker->optional()->numberBetween(50001, 100000),
      'discount_percent' => $this->faker->optional()->randomFloat(2, 0, 50),
      'reward_multiplier' => $this->faker->optional()->randomFloat(2, 1, 5),
    ];
  }
}
