<?php

namespace Tests\Unit;

use App\Events\InvoiceCompleted;
use App\Listeners\ProcessInvoicePoints;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MembershipLevel;
use App\Services\PointService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @testdox Xử lý tích điểm khi hóa đơn hoàn thành
 */
class ProcessInvoicePointsTest extends TestCase
{
  use RefreshDatabase;

  protected PointService $pointService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->pointService = new PointService();
  }

  /**
   * @testdox Cộng điểm tích lũy và điểm thưởng khi hóa đơn hoàn thành
   * @test
   */
  public function it_applies_points_when_invoice_is_completed()
  {
    Event::fake();

    $customer = Customer::factory()->create([
      'loyalty_points' => 100,
      'reward_points' => 200,
    ]);

    $invoice = Invoice::factory()->create([
      'customer_id' => $customer->id,
      'total_amount' => 250000,
      'invoice_status' => 'completed',
    ]);


    $listener = new ProcessInvoicePoints($this->pointService);
    $listener->handle(new InvoiceCompleted($invoice));

    $customer->refresh();

    $this->assertDatabaseHas('point_histories', [
      'customer_id' => $customer->id,
      'source_type' => 'invoice',
      'source_id' => $invoice->id,
    ]);

    $this->assertGreaterThan(100, $customer->loyalty_points);
    $this->assertGreaterThan(200, $customer->reward_points);
  }

  /**
   * @testdox Không cộng điểm nếu hóa đơn không có khách hàng
   * @test
   */
  public function it_does_not_apply_points_if_invoice_has_no_customer()
  {
    Event::fake();

    $invoice = Invoice::factory()->create([
      'customer_id' => null,
      'total_amount' => 250000,
      'invoice_status' => 'completed',
    ]);

    $listener = new ProcessInvoicePoints($this->pointService);
    $listener->handle(new InvoiceCompleted($invoice));

    $this->assertDatabaseMissing('point_histories', [
      'source_type' => 'invoice',
      'source_id' => $invoice->id,
    ]);
  }
  /**
   * @testdox Áp dụng hệ số nhân điểm thưởng vào ngày sinh nhật
   * @test
   */
  public function it_correctly_applies_bonus_multiplier_on_birthday()
  {
    Event::fake();

    $membershipLevel = MembershipLevel::factory()->create([
      'reward_multiplier' => 2, // Nhân đôi điểm thưởng vào ngày sinh nhật
    ]);

    $customer = Customer::factory()->create([
      'loyalty_points' => 100,
      'reward_points' => 200,
      'birthday' => Carbon::now()->format('Y-m-d'),
      'membership_level_id' => $membershipLevel->id,
    ]);

    $invoice = Invoice::factory()->create([
      'customer_id' => $customer->id,
      'total_amount' => 250000,
      'invoice_status' => 'completed',
    ]);

    $listener = new ProcessInvoicePoints($this->pointService);
    $listener->handle(new InvoiceCompleted($invoice));

    $customer->refresh();

    $expectedBonusPoints = floor(($invoice->total_amount / 25000) * 2); // Nhân đôi điểm thưởng

    $this->assertEquals(200 + $expectedBonusPoints, $customer->reward_points);
  }

  /**
   * @testdox Không áp dụng hệ số nhân nếu không phải sinh nhật
   * @test
   */
  public function it_does_not_apply_bonus_multiplier_if_not_birthday()
  {
    Event::fake();

    $membershipLevel = MembershipLevel::factory()->create([
      'reward_multiplier' => 2,
    ]);

    $customer = Customer::factory()->create([
      'loyalty_points' => 100,
      'reward_points' => 200,
      'birthday' => Carbon::now()->subDays(1)->format('Y-m-d'), // Hôm qua là sinh nhật
      'membership_level_id' => $membershipLevel->id,
    ]);

    $invoice = Invoice::factory()->create([
      'customer_id' => $customer->id,
      'total_amount' => 250000,
      'invoice_status' => 'completed',
    ]);

    $listener = new ProcessInvoicePoints($this->pointService);
    $listener->handle(new InvoiceCompleted($invoice));

    $customer->refresh();

    $expectedBonusPoints = floor($invoice->total_amount / 25000); // Không nhân đôi điểm

    $this->assertEquals(200 + $expectedBonusPoints, $customer->reward_points);
  }

  /**
   * @testdox Không áp dụng hệ số nhân nếu khách hàng đã nhận thưởng sinh nhật trong năm nay
   * @test
   */
  public function it_does_not_apply_birthday_bonus_if_customer_has_already_received_bonus_this_year()
  {
    Event::fake();

    $membershipLevel = MembershipLevel::factory()->create([
      'reward_multiplier' => 2,
    ]);

    $customer = Customer::factory()->create([
      'loyalty_points' => 100,
      'reward_points' => 200,
      'birthday' => Carbon::now()->format('Y-m-d'),
      'membership_level_id' => $membershipLevel->id,
      'last_birthday_bonus_date' => Carbon::now()->startOfYear(), // Đã nhận trong năm nay
    ]);

    $invoice = Invoice::factory()->create([
      'customer_id' => $customer->id,
      'total_amount' => 250000,
      'invoice_status' => 'completed',
    ]);

    $listener = new ProcessInvoicePoints($this->pointService);
    $listener->handle(new InvoiceCompleted($invoice));

    $customer->refresh();

    $expectedBonusPoints = floor($invoice->total_amount / 25000); // Không nhân đôi vì đã nhận thưởng

    $this->assertEquals(200 + $expectedBonusPoints, $customer->reward_points);
  }
}
