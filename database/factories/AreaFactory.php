<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Area;
use App\Models\Branch;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Area>
 */
class AreaFactory extends Factory
{
  protected $model = Area::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'name' => $this->faker->word . ' ' . $this->faker->randomElement(['Khu A', 'Khu B', 'Khu C']),
      'branch_id' => Branch::factory(),
      'note' => $this->faker->sentence,
    ];
  }
}
