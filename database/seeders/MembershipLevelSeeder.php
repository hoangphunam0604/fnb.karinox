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
        'birthday_gift' =>  'Tiếp tục chi tiêu thăng hạng để nhận ưu đãi hấp dẫn',
        'upgrade_reward_content'  =>  'Đăng ký lần đầu tặng voucher giảm giá 5% sử dụng dịch vụ tại Karinox Coffee & Pippy Kids, Pippy FC'
      ],
      [
        'rank' => 2,
        'name' => 'Gold',
        'min_spent' => 200,
        'max_spent' => 499,
        'reward_multiplier' => 1,
        'birthday_gift' =>  '
          •	Tặng voucher 1 đồ uống miễn phí nhân ngày sinh nhật<br>
          •	Tặng voucher giảm giá 10% sử dụng dịch vụ tại Karinox Coffee & Pippy Kids, Pippy FC
        ',
        'upgrade_reward_content'  =>  '
          •	Tặng voucher giảm 20% sử dụng dịch vụ tại Karinox Coffee & Pippy Kids, Pippy FC<br>
          •	Tặng 1 vé khu vui chơi Pippy Kids<br>
          •	Tặng móc khóa ngẫu nhiên
        '
      ],
      [
        'rank' => 3,
        'name' => 'Diamond',
        'min_spent' => 500,
        'max_spent' => null,
        'reward_multiplier' => 2,
        'birthday_gift' =>  '
          •	Tặng voucher 2 đồ uống miễn phí nhân ngày sinh nhật<br>
          •	Tặng voucher giảm giá 20% sử dụng dịch vụ tại Karinox Coffee & Pippy Kids, Pippy FC<br>
          •	X2 tích điểm trong ngày sinh nhật
        ',
        'upgrade_reward_content'  =>  '
          •	Tặng voucher giảm 50% sử dụng dịch vụ tại Karinox Coffee & Pippy Kids, Pippy FC<br>
          •	Tặng 2 vé khu vui chơi Pippy Kids<br>
          •	Giỏ quà Karinox, Thú Bông
        '
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
          'birthday_gift' => $level['birthday_gift'],
          'updated_at' => now(),
        ]
      );
    }
  }
}
