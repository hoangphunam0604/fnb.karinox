<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
  protected $model = Area::class;

  public function definition(): array
  {
    return [
      'name' => 'Khu vực ' . $this->faker->word,
      'branch_id' => Branch::factory(),
      'note' => $this->faker->optional()->sentence,
    ];
  }
}
