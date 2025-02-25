<?php

namespace Database\Factories;

use App\Enums\TableAndRoomStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TableAndRoom;
use App\Models\Area;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TableAndRoom>
 */
class TableAndRoomFactory extends Factory
{

  protected $model = TableAndRoom::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'name' => 'Bàn ' . $this->faker->numberBetween(1, 50),
      'area_id' => Area::factory(),
      'capacity' => $this->faker->numberBetween(2, 12),
      'status' => TableAndRoomStatus::fake()->value,
      'note' => $this->faker->sentence,
    ];
  }
}
