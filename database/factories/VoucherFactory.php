<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = Voucher::class;

  /**
   * Define the model's default state.
   *
   * @return array
   */
  public function definition()
  {
    $discountType = DiscountType::fake()->value;
    $discountValue = $discountType === 'fixed'
      ? $this->faker->randomFloat(2, 10, 100)  // Giảm giá 10 - 100 nếu là cố định
      : $this->faker->randomFloat(2, 5, 50);   // 5% - 50% nếu là percentage

    $maxDiscount = $discountType === 'percentage'
      ? $this->faker->randomFloat(2, 50, 200)  // Nếu là phần trăm, có giới hạn
      : null;

    return [
      'code' => 'VC' . (string) Str::uuid() . now()->timestamp . mt_rand(100, 9999999),
      'discount_type' => $discountType,
      'discount_value' => $discountValue,
      'max_discount' => $maxDiscount,/* 
      'start_date' => now()->subDays(2),
      'end_date' => now()->addDays($this->faker->numberBetween(7, 30)), // Kết thúc trong vòng 1 tháng */
      'applied_count' => 0, // Mặc định chưa có ai sử dụng
      'usage_limit' => $this->faker->optional()->numberBetween(10, 100),
      'is_active' => true,
    ];
  }
}
