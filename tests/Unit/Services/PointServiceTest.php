<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PointService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PointServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $pointService;
  protected $orderService;
  protected $systemSettingService;

  protected function setUp(): void
  {
    parent::setUp();

    $this->orderService = Mockery::spy(OrderService::class);
    $this->app->instance(OrderService::class, $this->orderService);

    $this->systemSettingService = Mockery::spy(SystemSettingService::class);
    $this->app->instance(SystemSettingService::class, $this->systemSettingService);
    $this->pointService = app(PointService::class);
  }


  public function test_update_points_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 50, 'reward_points' => 30]);

    $this->pointService->updatePoints($customer, 20, 10, 'earn');

    $customer->refresh();
    $this->assertEquals(70, $customer->loyalty_points);
    $this->assertEquals(40, $customer->reward_points);

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'transaction_type' => 'earn',
      'loyalty_points_changed' => 20,
      'reward_points_changed' => 10,
      'loyalty_points_after' => 70,
      'reward_points_after' => 40,
    ]);
  }

  public function test_earn_points_increases_loyalty_and_reward_points()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 0, 'reward_points' => 0]);

    $this->pointService->earnPoints($customer, 10, 5);

    $customer->refresh();
    $this->assertEquals(10, $customer->loyalty_points);
    $this->assertEquals(5, $customer->reward_points);
  }

  public function test_redeem_points_decreases_loyalty_and_reward_points()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 50, 'reward_points' => 30]);

    $this->pointService->redeemPoints($customer, 20, 10);

    $customer->refresh();
    $this->assertEquals(30, $customer->loyalty_points);
    $this->assertEquals(20, $customer->reward_points);
  }

  public function test_use_reward_points_decreases_only_reward_points()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 50, 'reward_points' => 30]);

    $this->pointService->useRewardPoints($customer, 15);

    $customer->refresh();
    $this->assertEquals(50, $customer->loyalty_points);
    $this->assertEquals(15, $customer->reward_points);
  }

  public function test_restore_reward_points_used_on_order_cancellation()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 100, 'reward_points' => 50]);
    $order = Order::factory()->create([
      'customer_id' => $customer->id,
      'earned_loyalty_points' => 20,
      'earned_reward_points' => 10,
    ]);

    $this->pointService->restoreRewardPointsUsedOnInvoiceCancellation($order);

    $customer->refresh();
    $this->assertEquals(120, $customer->loyalty_points);
    $this->assertEquals(60, $customer->reward_points);
  }

  public function test_add_points_on_invoice_completion()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 100, 'reward_points' => 50]);
    $invoice = Invoice::factory()->create([
      'customer_id' => $customer->id,
      'earned_loyalty_points' => 30,
      'earned_reward_points' => 15,
    ]);

    $this->pointService->addPointsOnInvoiceCompletion($invoice);

    $customer->refresh();
    $this->assertEquals(130, $customer->loyalty_points);
    $this->assertEquals(65, $customer->reward_points);
  }

  public function test_restore_points_on_invoice_cancellation()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 100, 'reward_points' => 50]);
    $invoice = Invoice::factory()->create([
      'customer_id' => $customer->id,
      'earned_loyalty_points' => 30,
      'earned_reward_points' => 15,
    ]);

    $this->pointService->restorePointsOnInvoiceCancellation($invoice);

    $customer->refresh();
    $this->assertEquals(70, $customer->loyalty_points);
    $this->assertEquals(35, $customer->reward_points);
  }

  public function test_use_reward_points_for_order()
  {
    $customer = Customer::factory()->create(['reward_points' => 50]);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'subtotal_price' => 100000, 'total_price' => 100000]);

    // Mock updateTotalPrice để không gọi thực tế
    $this->orderService->shouldReceive('updateTotalPrice')
      ->once()
      ->with(\Mockery::type(Order::class), 20)
      ->andReturnUsing(function ($order) {
        $order->update([
          'total_price' => 70000
        ]);
      });

    // Mock getRewardPointConversionRate để không gọi thực tế
    $this->systemSettingService->shouldReceive('getRewardPointConversionRate')
      ->twice()
      ->andReturn(1000); // giả SystemSettingService::getRewardPointConversionRate trả về 1000 tương đương tỷ lệ đổi 1 điểm = 1000đ

    $this->pointService->useRewardPointsForOrder($order, 30); // Sử dụng 30 điểm, quy đổi 30.000đ để thanh toán

    $customer->refresh();
    $order->refresh();
    $this->assertEquals(20, $customer->reward_points);
    $this->assertEquals(30, $order->reward_points_used);
    $this->assertEquals(30000, $order->reward_discount);
    $this->assertEquals(70000, $order->total_price);
  }

  public function test_validate_reward_points_usage_to_order_throws_exception()
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Số điểm sử dụng vượt quá số điểm hiện có.');

    $customer = Customer::factory()->create(['reward_points' => 10]);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'total_price' => 100]);

    $this->pointService->useRewardPointsForOrder($order, 20);
  }
}
