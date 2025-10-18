<?php

namespace Database\Factories;

use App\Models\TableAndRoom;
use App\Models\Area;
use App\Enums\TableAndRoomStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TableAndRoomFactory extends Factory
{
  protected $model = TableAndRoom::class;

  public function definition(): array
  {
    return [
      'name' => 'Bàn ' . $this->faker->numberBetween(1, 99),
      'area_id' => Area::factory(),
      'capacity' => $this->faker->numberBetween(2, 8),
      'status' => TableAndRoomStatus::AVAILABLE,
      'note' => $this->faker->optional()->sentence,
    ];
  }
}
