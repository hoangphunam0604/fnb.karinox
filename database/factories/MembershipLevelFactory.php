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
      'reward_multiplier' => $this->faker->optional()->randomFloat(2, 1, 5),
    ];
  }
}
