<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\PointHistory;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @testdox Quản lý tích điểm và sử dụng điểm thưởng cho khách hàng
 */
class PointServiceTest extends TestCase
{
  use RefreshDatabase;

  protected PointService $pointService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->pointService = new PointService();
  }

  /**
   * @testdox Cộng điểm tích lũy và điểm thưởng thành công
   * @test
   */
  public function it_can_add_loyalty_and_reward_points()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 100, 'reward_points' => 200]);

    $history = $this->pointService->addPoints($customer, 50, 30, 'invoice', 123);

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'loyalty_points_changed' => 50,
      'reward_points_changed' => 30,
      'source_type' => 'invoice',
      'source_id' => 123,
    ]);

    $this->assertEquals(150, $customer->refresh()->loyalty_points);
    $this->assertEquals(230, $customer->refresh()->reward_points);
  }

  /**
   * @testdox Sử dụng điểm thưởng thành công
   * @test
   */
  public function it_can_redeem_reward_points()
  {
    $customer = Customer::factory()->create(['reward_points' => 500]);

    $history = $this->pointService->redeemPoints($customer, 200, 'voucher', 789);

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'reward_points_changed' => -200,
      'usage_type' => 'voucher',
      'usage_id' => 789,
    ]);

    $this->assertEquals(300, $customer->refresh()->reward_points);
  }

  /**
   * @testdox Không thể sử dụng điểm thưởng nếu không đủ điểm
   * @test
   */
  public function it_throws_exception_if_insufficient_reward_points()
  {
    $customer = Customer::factory()->create(['reward_points' => 100]);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Khách hàng không đủ điểm thưởng để sử dụng.");

    $this->pointService->redeemPoints($customer, 200, 'voucher', 789);
  }

  /**
   * @testdox Lịch sử tích điểm được ghi lại chính xác
   * @test
   */
  public function it_logs_loyalty_point_history_correctly()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 50]);

    $history = $this->pointService->addPoints($customer, 100, 0, 'event', 555);

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'loyalty_points_changed' => 100,
      'reward_points_changed' => 0,
      'source_type' => 'event',
      'source_id' => 555,
    ]);

    $this->assertEquals(150, $customer->refresh()->loyalty_points);
  }

  /**
   * @testdox Lịch sử sử dụng điểm thưởng được ghi lại chính xác
   * @test
   */
  public function it_logs_reward_point_history_correctly()
  {
    $customer = Customer::factory()->create(['reward_points' => 300]);

    $history = $this->pointService->redeemPoints($customer, 100, 'gift', 777);

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'loyalty_points_changed' => 0,
      'reward_points_changed' => -100,
      'usage_type' => 'gift',
      'usage_id' => 777,
    ]);

    $this->assertEquals(200, $customer->refresh()->reward_points);
  }

  /**
   * @testdox Lấy danh sách lịch sử điểm của khách hàng
   * @test
   */
  public function it_can_get_customer_point_history()
  {
    $customer = Customer::factory()->create(['loyalty_points' => 1000, 'reward_points' => 500]);

    // Tạo 5 lịch sử tích điểm
    PointHistory::factory()->count(5)->create([
      'customer_id' => $customer->id,
      'transaction_type' => 'earn',
      'loyalty_points_changed' => 50,
      'reward_points_changed' => 20,
    ]);

    $history = $this->pointService->getCustomerPointHistory($customer, 5);

    $this->assertCount(5, $history);
    $this->assertEquals($customer->id, $history->first()->customer_id);
  }

  /**
   * @testdox Lịch sử điểm trả về đúng số lượng yêu cầu
   * @test
   */
  public function it_returns_limited_point_history()
  {
    $customer = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    // Tạo 10 lịch sử điểm
    PointHistory::factory()->count(10)->create([
      'customer_id' => $customer->id,
    ]);

    $history = $this->pointService->getCustomerPointHistory($customer, 3);

    $this->assertCount(3, $history);
  }
  /**
   * @testdox Lịch sử điểm lưu điểm trước khi thay đổi
   * @test
   */
  public function it_stores_previous_points_correctly()
  {
    $customer = Customer::factory()->create([
      'loyalty_points' => 100,
      'reward_points' => 200,
    ]);

    $history = $this->pointService->addPoints($customer, 50, 30, 'event', 999, 'Thưởng sinh nhật');

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'previous_loyalty_points' => 100,
      'previous_reward_points' => 200,
      'loyalty_points_changed' => 50,
      'reward_points_changed' => 30,
      'loyalty_points_after' => 150,
      'reward_points_after' => 230,
      'note' => 'Thưởng sinh nhật',
    ]);
  }
}
