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
        'discount_type' => 'percentage',
        'discount_value' => 5,
        'per_customer_limit' => 1,
        'is_active' => true,
        'disable_holiday' => true, // Không áp dụng vào lễ, tết
      ],
      [
        'code' => 'IAMGOLD',
        'discount_type' => 'percentage',
        'discount_value' => 5,
        'per_customer_limit' => 2,
        'is_active' => true,
        'applicable_membership_levels' => json_encode([2]),
      ],
      [
        'code' => 'IAMDIAMOND',
        'discount_type' => 'percentage',
        'discount_value' => 8,
        'per_customer_limit' => 3,
        'is_active' => true,
        'applicable_membership_levels' => json_encode([3]),
      ],
      [
        'code' => 'HAPPYMONDAY',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_order_value' => null,
        'usage_limit' => null,
        'per_customer_limit' => null,
        'is_active' => true,
        'valid_days_of_week' => json_encode([6]), //Tuần cuối cùng
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
