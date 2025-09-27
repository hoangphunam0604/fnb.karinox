<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
  public function run()
  {
    $now = Carbon::now();
    $currentYear = $now->year;

    $vouchers = [
      [
        'code' => 'WELCOME',
        'description' =>  'Đăng ký lần đầu tặng voucher giảm giá 5%',
        'voucher_type'  =>  'standard',
        'discount_type' => 'percentage',
        'discount_value' => 5,
        'per_customer_limit' => 1,
        'is_active' => true,
        'disable_holiday' => true, // Không áp dụng vào lễ, tết
        'applicable_membership_levels' => [1, 2, 3], // Áp dụng cho tất cả thành viên
      ],
      [
        'code' => 'HAPPYMONDAY',
        'description' =>  'Giảm giá 10% cho tất cả thành viên vào ngày thứ 2 cuối cùng của tháng (không áp dụng lễ, tết)',
        'voucher_type'  =>  'standard',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_order_value' => null,
        'usage_limit' => null,
        'per_customer_limit' => null,
        'is_active' => true,
        'disable_holiday' => true, // Không áp dụng vào lễ, tết
        'applicable_membership_levels' => [1, 2, 3], // Áp dụng cho tất cả thành viên
        'valid_days_of_week' => [1], //1 = Thứ 2
        'valid_weeks_of_month' => [6], //1 tháng tối đa 5 tuần, Thêm 6 để xác định tuần cuối cùng      
      ],
      [
        'code' => 'GOLD_MEMBER',
        'description' =>  'Giảm trực tiếp 5% cho mỗi hóa đơn (tối đa 2 hóa đơn/ngày)',
        'voucher_type'  =>  'standard',
        'discount_type' => 'percentage',
        'discount_value' => 5,
        'per_customer_daily_limit' => 2,
        'is_active' => true,
        'applicable_membership_levels' => [2],
      ],
      [
        'code' => 'DIAMOND_MEMBER',
        'description' =>  'Giảm trực tiếp 8% cho mỗi hóa đơn (tối đa 3 hóa đơn/ngày)',
        'voucher_type'  =>  'standard',
        'discount_type' => 'percentage',
        'discount_value' => 8,
        'per_customer_daily_limit' => 3,
        'is_active' => true,
        'applicable_membership_levels' => [3],
      ],
    ];

    foreach ($vouchers as $voucher) {
      Voucher::updateOrCreate(
        ['code' => $voucher['code']], // Điều kiện tìm kiếm
        $voucher
      );
    }
  }
}
