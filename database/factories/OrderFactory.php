<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Area>
 */
class OrderFactory extends Factory
{
  protected $model = Order::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'branch_id' => Branch::factory(),
      'customer_id' => $this->faker->boolean(50) ?  Customer::factory() : null,
      'order_code'  => function () {
        do {
          $code = 'ORDER' . Str::uuid()->toString() . now()->timestamp . mt_rand(100, 9999999);
        } while (\App\Models\Invoice::where('code', $code)->exists());
        return $code;
      },
      'order_status' => OrderStatus::fake()->value,
      'note' => $this->faker->sentence(),
      'total_price' => 0, // Cập nhật sau khi thêm OrderItem
    ];
  }

  public function withItems($count = 3)
  {
    return $this->afterCreating(function (Order $order) use ($count) {
      $items = OrderItem::factory()->count($count)->create(['order_id' => $order->id]);
      $order->update(['total_price' => $items->sum('total_price')]);
    });
  }
}
