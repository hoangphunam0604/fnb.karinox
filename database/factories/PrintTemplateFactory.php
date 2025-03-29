<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PrintTemplateFactory extends Factory
{
  public function definition(): array
  {
    $type = $this->faker->randomElement(['invoice', 'label', 'kitchen']);

    return [
      'type'        => $type,
      'name'        => ucfirst($type) . ' Template ' . $this->faker->unique()->word,
      'description' => $this->faker->sentence,
      'content'     => "<h3>{{ tenQuan }}</h3><p>Bàn: {{ ban }}</p><p>Ngày: {{ ngay }}</p>",
      'is_default'  => false,
      'is_active'   => true,
    ];
  }

  public function default(): static
  {
    return $this->state([
      'is_default' => true,
    ]);
  }

  public function inactive(): static
  {
    return $this->state([
      'is_active' => false,
    ]);
  }
}
