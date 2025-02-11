<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
  protected $model = Invoice::class;

  public function definition()
  {
    return [
      'code' => strtoupper(Str::random(8)), // Mã sản phẩm ngẫu nhiên (VD: AB12CD34)
      'order_id' => Order::factory(),
      'customer_id' => Customer::factory(),
      'branch_id' => Branch::factory(),
      'discount_amount' => $this->faker->randomElement([0, 5000, 10000, 20000]),
      'paid_amount' => $this->faker->randomElement([0, 50000, 100000, 200000]),
      'invoice_status' => $this->faker->randomElement(['pending', 'completed']),
      'payment_status' => $this->faker->randomElement(['unpaid', 'partial', 'paid', 'refunded']),
      'note' => $this->faker->sentence(),
      'created_at' => now(),
      'updated_at' => now(),
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
