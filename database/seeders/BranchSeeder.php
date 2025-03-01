<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $branches = [
      [
        'name' => 'Gà Rán Pippy Kids',
        'address' => 'Lô TM27-1 Hoàng Diệu',
      ],
      [
        'name' => 'Karinox Coffee',
        'address' => 'Lô TM27-1 Hoàng Diệu',
      ],
      [
        'name' => 'Khu Vui Chơi Pippy Kids',
        'address' => '7Lô TM27-1 Hoàng Diệu',
      ],
    ];

    foreach ($branches as $branch) {
      Branch::create($branch);
    }
  }
}
