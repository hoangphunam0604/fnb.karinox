<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherUsageFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = VoucherUsage::class;

  /**
   * Define the model's default state.
   *
   * @return array
   */
  public function definition()
  {
    $invoiceTotalBeforeDiscount = $this->faker->randomFloat(2, 100000, 1000000); // Tổng tiền trước giảm giá
    $discountAmount = $this->faker->randomFloat(2, 50000, $invoiceTotalBeforeDiscount * 0.3); // Giảm tối đa 30%
    $invoiceTotalAfterDiscount = $invoiceTotalBeforeDiscount - $discountAmount;

    return [
      'voucher_id' => Voucher::factory(),
      'order_id' => Order::factory(),
      'customer_id' => $this->faker->boolean(80) ? Customer::factory() : null, // 80% có customer_id, 20% null
      'invoice_id' => $this->faker->boolean(70) ? Invoice::factory() : null, // 70% có invoice_id, 30% null
      'used_at' => Carbon::now(),
      'discount_amount' => $discountAmount,
      'invoice_total_before_discount' => $invoiceTotalBeforeDiscount,
      'invoice_total_after_discount' => $invoiceTotalAfterDiscount,
    ];
  }
}
