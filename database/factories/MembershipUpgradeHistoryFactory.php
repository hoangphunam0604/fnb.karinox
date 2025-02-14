<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Models\MembershipUpgradeHistory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipUpgradeHistory>
 */
class MembershipUpgradeHistoryFactory extends Factory
{
  protected $model = MembershipUpgradeHistory::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'customer_id' => Customer::factory(),
      'old_membership_level_id' => MembershipLevel::factory(),
      'new_membership_level_id' => MembershipLevel::factory(),
      'upgraded_at' => Carbon::now(),
      'upgrade_reward_content' => $this->faker->sentence(5), // Nội dung quà tặng
      'reward_claimed' => $this->faker->boolean(30), // 30% đã nhận quà
    ];
  }
}
