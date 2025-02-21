<?php

namespace Tests\Unit\Services;

use App\Services\PointService;
use App\Services\Interfaces\PointServiceInterface;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PointHistory;
use App\Contracts\PointEarningTransaction;
use App\Contracts\RewardPointUsable;
use App\Models\MembershipLevel;
use App\Services\OrderService;
use App\Services\SystemSettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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

    /** @var OrderService $orderService */
    $this->orderService = Mockery::mock(OrderService::class);
    /** @var SystemSettingService $systemSettingService */
    $this->systemSettingService = Mockery::mock(SystemSettingService::class);

    $this->pointService = new PointService($this->orderService, $this->systemSettingService);
  }


  public function test_get_customer_point_history_returns_paginated_data()
  {
    $customer = Customer::factory()->create();
    PointHistory::factory()->count(5)->create(['customer_id' => $customer->id]);

    $result = $this->pointService->getCustomerPointHistory($customer);

    $this->assertInstanceOf(LengthAwarePaginator::class, $result);
  }


  public function test_update_points_increases_or_decreases_points_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 10, 'reward_points' => 5]);

    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) use ($customer) {
      return $callback();
    });

    $history = $this->pointService->updatePoints($customer, 5, 2, 'earn');

    $this->assertEquals(15, $customer->loyalty_points);
    $this->assertEquals(7, $customer->reward_points);
    $this->assertInstanceOf(PointHistory::class, $history);
  }


  public function test_earn_points_adds_points_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 10, 'reward_points' => 0]);
    $history = $this->pointService->earnPoints($customer, 5, 3);

    $this->assertEquals(15, $customer->loyalty_points);
    $this->assertEquals(3, $customer->reward_points);
    $this->assertEquals('earn', $history->transaction_type);
    $this->assertInstanceOf(PointHistory::class, $history);
  }


  public function test_redeem_points_deducts_points_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 10, 'reward_points' => 5]);
    $history = $this->pointService->redeemPoints($customer, 3, 2);

    $this->assertEquals(7, $customer->loyalty_points);
    $this->assertEquals(3, $customer->reward_points);
    $this->assertEquals('redeem', $history->transaction_type);
    $this->assertInstanceOf(PointHistory::class, $history);
  }


  public function test_transfer_used_points_to_invoice_updates_point_history()
  {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    $pointHistory = PointHistory::factory()->create([
      'customer_id' => $customer->id,
      'source_type' => 'order',
      'source_id' => $invoice->order_id,
      'usage_type' => 'discount',
      'usage_id' => $invoice->order_id,
    ]);

    $this->pointService->transferUsedPointsToInvoice($invoice);

    $pointHistory->refresh();
    $this->assertEquals('invoice', $pointHistory->source_type);
    $this->assertEquals($invoice->id, $pointHistory->source_id);
  }


  public function test_calculate_points_from_transaction_returns_correct_points()
  {
    $transaction = Mockery::mock(PointEarningTransaction::class);
    $transaction->shouldReceive('canEarnPoints')->andReturn(true);
    $transaction->shouldReceive('getTotalAmount')->andReturn(250000); //Giao dịch 250k
    $transaction->shouldReceive('getCustomer')->andReturn(Customer::factory()->make());

    $this->systemSettingService->shouldReceive('getPointConversionRate')->andReturn(25000); //Tỷ lệ quy đỏi 25k

    [$loyaltyPoints, $rewardPoints] = $this->pointService->calculatePointsFromTransaction($transaction);

    $this->assertEquals(10, $loyaltyPoints); //Nhận được 10 điểm
    $this->assertGreaterThanOrEqual(0, $rewardPoints);
  }


  public function test_earn_points_on_transaction_completion_applies_correct_points()
  {
    $transaction = Mockery::mock(PointEarningTransaction::class);
    $membershipLevel = MembershipLevel::factory()->create(['reward_multiplier' => 2]);
    $customer = Customer::factory()->create([
      'membership_level_id' => $membershipLevel->id,
      'birthday' => Carbon::now(),
      'loyalty_points' => 100,
      'reward_points' => 100,
    ]);

    $transaction->shouldReceive('canEarnPoints')->andReturn(true);
    $transaction->shouldReceive('getCustomer')->andReturn($customer);
    $transaction->shouldReceive('getTransactionType')->andReturn('invoice');
    $transaction->shouldReceive('getTransactionId')->andReturn(1);
    $transaction->shouldReceive('getTotalAmount')->andReturn(250000);
    $transaction->shouldReceive('getEarnedPointsNote')->andReturn('Earned from invoice');
    $transaction->shouldReceive('updatePoints');

    $this->systemSettingService->shouldReceive('getPointConversionRate')->andReturn(25000); //Tỷ lệ quy đỏi 25k

    $this->pointService->earnPointsOnTransactionCompletion($transaction);

    $this->assertEquals(110, $customer->loyalty_points); //Nhận được 10 điểm
    $this->assertEquals(120, $customer->reward_points); //Nhận được x2 tích điểm thưởng =  20đ
  }

  public function test_use_reward_points_applies_discount_correctly()
  {
    $transaction = Mockery::mock(RewardPointUsable::class);
    $customer = Customer::factory()->create(['loyalty_points' => 100, 'reward_points' => 50]);

    $transaction->shouldReceive('getCustomer')->andReturn($customer);
    $transaction->shouldReceive('getTransactionType')->andReturn('order');
    $transaction->shouldReceive('getTransactionId')->andReturn(1);
    $transaction->shouldReceive('getTotalAmount')->andReturn(250000);
    $transaction->shouldReceive('applyRewardPointsDiscount');

    $this->systemSettingService->shouldReceive('getRewardPointConversionRate')->andReturn(1);

    $this->pointService->useRewardPoints($transaction, 20);
    $this->assertEquals(30, $customer->reward_points);
  }


  public function test_restore_transaction_reward_points_restores_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 10, 'reward_points' => 50]);
    $transaction = Invoice::factory()->create([
      'customer_id' =>  $customer->id,
      'reward_points_used' => 10,
    ]);
    $this->pointService->restoreTransactionRewardPoints($transaction);
    $customer->refresh();
    $this->assertEquals(60, $customer->reward_points);
    $this->assertEquals(0, $transaction->reward_points_used);
  }


  public function test_restore_transaction_earned_points_restores_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 100, 'reward_points' => 50]);
    $transaction = Invoice::factory()->create([
      'customer_id' =>  $customer->id,
      'total_price' => 250000,
      'earned_loyalty_points' => 10,
      'earned_reward_points' => 10,
    ]);

    $this->pointService->restoreTransactionEarnedPoints($transaction);

    $customer->refresh();
    $this->assertEquals(90, $customer->loyalty_points);
    $this->assertEquals(40, $customer->reward_points);
    $this->assertEquals(0, $customer->earned_loyalty_points);
    $this->assertEquals(0, $customer->earned_reward_points);
  }

  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }
}
