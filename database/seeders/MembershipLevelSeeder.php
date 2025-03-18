<?php

namespace Database\Seeders;

use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;

class MembershipLevelSeeder extends Seeder
{
  public function run()
  {
    $levels = [
      [
        'rank' => 1,
        'name' => 'Silver',
        'min_spent' => 0,
        'max_spent' => 199,
        'reward_multiplier' => 1,
      ],
      [
        'rank' => 2,
        'name' => 'Gold',
        'min_spent' => 200,
        'max_spent' => 499,
        'reward_multiplier' => 1,
      ],
      [
        'rank' => 3,
        'name' => 'Diamond',
        'min_spent' => 500,
        'max_spent' => null,
        'reward_multiplier' => 2,
      ],
    ];

    foreach ($levels as $level) {
      MembershipLevel::updateOrCreate(
        ['name' => $level['name']], // Điều kiện tìm kiếm để tránh trùng
        [
          'rank' => $level['rank'],
          'min_spent' => $level['min_spent'],
          'max_spent' => $level['max_spent'],
          'reward_multiplier' => $level['reward_multiplier'],
          'updated_at' => now(),
        ]
      );
    }
  }
}
