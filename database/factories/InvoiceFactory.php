<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
  protected $model = Invoice::class;

  public function definition()
  {
    $totalPrice = $this->faker->randomFloat(2, 50000, 500000);
    $paidAmount = $totalPrice; // Giả sử khách hàng thanh toán đủ
    $changeAmount = 0; // Mặc định không có tiền thừa

    return [
      'code' => function () {
        do {
          $code = 'HD' . Str::uuid()->toString() . now()->timestamp . mt_rand(100, 9999999);
        } while (\App\Models\Invoice::where('code', $code)->exists());
        return $code;
      },

      'branch_id' => Branch::factory(),
      'order_id' => Order::factory(),
      'total_price' => $totalPrice,
      'paid_amount' => $paidAmount,
      'change_amount' => $changeAmount,
      'voucher_id' => $this->faker->boolean(50) ? Voucher::factory() : null,
      'sales_channel' => $this->faker->randomElement(['online', 'offline']),
      'invoice_status' => InvoiceStatus::fake()->value,
      'payment_status' => PaymentStatus::fake()->value,
      'payment_method' => $this->faker->randomElement(['cash', 'credit_card', 'bank_transfer', 'e_wallet']),
      'note' => $this->faker->optional()->sentence(),
      'customer_id' => $this->faker->boolean(70) ? Customer::factory() : null, // 70% có khách hàng
      'loyalty_card_number' => $this->faker->boolean(50) ? $this->faker->unique()->numerify('LC######') : null,
      'customer_name' => $this->faker->name,
      'customer_phone' => $this->faker->phoneNumber,
      'customer_email' => $this->faker->optional()->safeEmail(),
      'customer_address' => $this->faker->optional()->address(),
    ];
  }

  /**
   * Trạng thái hóa đơn hoàn tất
   */
  public function completed()
  {
    return $this->state([
      'invoice_status' => 'completed',
      'payment_status' => 'paid',
    ]);
  }

  /**
   * Trạng thái hóa đơn chưa thanh toán
   */
  public function unpaid()
  {
    return $this->state([
      'invoice_status' => 'pending',
      'payment_status' => 'unpaid',
    ]);
  }

  /**
   * Hóa đơn với số tiền đã thanh toán một phần
   */
  public function partiallyPaid()
  {
    return $this->state([
      'invoice_status' => 'pending',
      'payment_status' => 'partial',
      'paid_amount' => 50000,
    ]);
  }
}
