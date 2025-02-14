<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\MembershipLevel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
  protected $model = Customer::class;

  public function definition(): array
  {
    return [
      'membership_level_id' => MembershipLevel::factory(),
      'loyalty_card_number' => $this->faker->unique()->numerify('CARD########'),
      'last_purchase_at' => $this->faker->dateTimeThisYear(),
      'status' => $this->faker->randomElement(['active', 'inactive', 'banned']),
      'name' => $this->faker->name,
      'email' => $this->faker->unique()->safeEmail,
      'phone' => $this->faker->unique()->phoneNumber,
      'loyalty_points' => $this->faker->numberBetween(0, 10000), // Điểm tích lũy dùng để xét hạng
      'reward_points' => $this->faker->numberBetween(0, 5000), // Điểm thưởng dùng để đổi quà

    ];
  }
}
