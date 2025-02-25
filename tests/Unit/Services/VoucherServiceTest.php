<?php

namespace Tests\Unit\Services;

use App\Enums\Msg;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;


class VoucherServiceTest extends TestCase
{
  use RefreshDatabase; // Reset database trước mỗi test

  protected $voucherService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->voucherService = new VoucherService();
  }

  #[Test]
  public function it_can_create_a_voucher()
  {
    $voucherData = [
      'code' => 'TEST50',
      'discount_type' => 'percentage',
      'discount_value' => 10,
      'max_discount' => 50,
      'min_order_value' => 200,
      'start_date' => now(),
      'end_date' => now()->addDays(30),
      'usage_limit' => 100,
      'is_active' => true,
    ];

    $voucher = $this->voucherService->create($voucherData);

    $this->assertDatabaseHas('vouchers', ['code' => 'TEST50']);
    $this->assertEquals(10, $voucher->discount_value);
  }
  #[Test]
  public function it_can_update_a_voucher()
  {
    $voucher = Voucher::factory()->create(['code' => 'UPDATE50']);
    $updatedData = ['discount_value' => 20];

    $this->voucherService->update($voucher, $updatedData);

    $this->assertDatabaseHas('vouchers', ['code' => 'UPDATE50', 'discount_value' => 20]);
  }
  #[Test]
  public function it_can_delete_a_voucher()
  {
    $voucher = Voucher::factory()->create();
    $this->voucherService->delete($voucher);

    $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
  }
  #[Test]
  public function it_can_find_voucher_by_code()
  {
    $voucher = Voucher::factory()->create(['code' => 'FINDME']);

    $foundVoucher = $this->voucherService->findByCode('FINDME');

    $this->assertNotNull($foundVoucher);
    $this->assertEquals('FINDME', $foundVoucher->code);
  }
  #[Test]
  public function it_can_get_paginated_vouchers()
  {
    Voucher::factory()->count(15)->create();

    $paginatedVouchers = $this->voucherService->getAllPaginated(10);

    $this->assertCount(10, $paginatedVouchers); // Kiểm tra có 10 voucher được lấy ra
  }
  #[Test]
  public function it_can_get_valid_vouchers()
  {
    $validVoucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDay(),
    ]);

    $expiredVoucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDays(10),
      'end_date' => now()->subDays(5),
    ]);

    $validVouchers = $this->voucherService->getValidVouchers();

    $this->assertCount(1, $validVouchers);
    $this->assertEquals($validVoucher->id, $validVouchers->first()->id);
  }
  #[Test]
  public function it_checks_if_a_voucher_is_valid()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'min_order_value' => 100,
      'usage_limit' => 10,
      'applied_count' => 5,
    ]);

    $result = $this->voucherService->isValid($voucher, 150); // Đơn hàng 150
    $this->assertTrue($result);

    $result = $this->voucherService->isValid($voucher, 50); // Đơn hàng 50 (nhỏ hơn min_order_value)
    $this->assertFalse($result);
  }
  #[Test]
  public function it_does_not_allow_voucher_exceeding_usage_limit()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'usage_limit' => 5,
      'applied_count' => 5, // Đã đạt giới hạn
    ]);

    $result = $this->voucherService->isValid($voucher, 200);
    $this->assertFalse($result);
  }

  #[Test]
  public function it_applies_a_voucher_and_updates_usage()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'discount_type' => 'fixed',
      'discount_value' => 20,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'usage_limit' => 10,
      'applied_count' => 5,
    ]);

    // Tạo đơn hàng nhưng chưa dùng voucher
    $order = Order::factory()->create(['order_status' => 'confirmed', 'total_price' => 200]);

    $result = $this->voucherService->applyVoucher($voucher->code, $order);

    dump($result);

    $this->assertTrue($result['success']);
    $this->assertEquals(20, $result['discount']);
    $this->assertEquals(180, $result['final_total']);

    $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'applied_count' => 6]);
  }


  #[Test]
  public function it_does_not_apply_invalid_voucher()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => false, // Voucher không hợp lệ
    ]);

    // Tạo đơn hàng nhưng chưa dùng voucher
    $order = Order::factory()->create(['order_status' => 'confirmed']);

    $result = $this->voucherService->applyVoucher($voucher, $order);

    $this->assertFalse($result['success']);
  }
  #[Test]
  public function it_checks_voucher_valid_for_membership_level()
  {
    $membershipLevel = MembershipLevel::factory()->create(['id' => 1, 'name' => "Bronze"]);
    $customer = Customer::factory()->create(['membership_level_id' => $membershipLevel->id]);
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'per_customer_limit'  =>  null,
      'per_customer_daily_limit'  =>  null,
      'applicable_membership_levels' => json_encode([$membershipLevel->id, 2]),
    ]);
    $result = $this->voucherService->isValid($voucher, 200, $customer->id);
    $this->assertTrue($result); // Không kiểm tra hạng thành viên
  }
  #[Test]
  public function it_fails_if_membership_level_not_allowed()
  {
    $membershipLevel = MembershipLevel::factory()->create(['id' => 1, 'name' => "Bronze"]);
    $customer = Customer::factory()->create(['membership_level_id' => $membershipLevel->id]);

    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'applicable_membership_levels' => json_encode([2, 3]),
    ]);

    // Khách hàng không thuộc hạng thành viên hợp lệ
    $result = $this->voucherService->isValid($voucher, 200,  $customer->id);
    $this->assertFalse($result);
  }
  #[Test]
  public function it_checks_voucher_valid_for_today()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_days_of_week' => json_encode([now()->dayOfWeek]), // Chỉ hợp lệ hôm nay
    ]);

    $this->assertTrue($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_fails_if_voucher_not_valid_today()
  {

    $currentDayOfWeek = now()->dayOfWeek;
    $valid_days_of_week = [0, 1, 2, 3, 4, 5, 6];

    // Loại bỏ ngày hiện tại khỏi danh sách hợp lệ
    $filtered_days = array_values(array_diff($valid_days_of_week, [$currentDayOfWeek]));

    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_days_of_week' => json_encode($filtered_days), // Chỉ hợp lệ vào các ngày trong tuần, trừ ngày đang test
    ]);
    $this->assertFalse($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_checks_voucher_valid_for_current_week_of_month()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_weeks_of_month' => json_encode([ceil(now()->day / 7)]), // Hợp lệ trong tuần này
    ]);

    $this->assertTrue($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_fails_if_voucher_not_valid_this_week()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_weeks_of_month' => json_encode([1]), // Chỉ hợp lệ tuần đầu tiên
    ]);

    if (ceil(now()->day / 7) !== 1) {
      $this->assertFalse($this->voucherService->isValid($voucher, 200));
    }
  }
  #[Test]
  public function it_checks_voucher_valid_for_current_month()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_months' => json_encode([now()->month]), // Hợp lệ trong tháng này
    ]);

    $this->assertTrue($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_fails_if_voucher_not_valid_this_month()
  {
    $currentMonth = now()->format('m');
    $prevMonth = date('m', strtotime($currentMonth . ' -1 months'));
    $nextMonth = date('m', strtotime($currentMonth . ' +1 months'));

    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_months' => json_encode([$prevMonth, $nextMonth]), // Chỉ hợp lệ vào tháng trước và tháng sau, không bao gồm tháng đang test
    ]);

    $this->assertFalse($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_checks_voucher_valid_for_current_time_range()
  {
    $currentTime = now()->format('H:i');
    $invalidTimeRange = date('H:i', strtotime($currentTime . ' -2 hours')) . '-' . date('H:i', strtotime($currentTime . ' +3 hours'));

    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_time_ranges' => json_encode([$invalidTimeRange]), // Hợp lệ trong khoảng thời gian -2h -> +3h của hiện tại
    ]);

    $this->assertTrue($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_fails_if_voucher_not_valid_for_current_time()
  {
    $currentTime = now()->format('H:i');
    $invalidTimeRange = date('H:i', strtotime($currentTime . ' +2 hours')) . '-' . date('H:i', strtotime($currentTime . ' +3 hours'));
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_time_ranges' => json_encode([$invalidTimeRange]), // Chỉ hợp lệ từ +2h đến +3h từ hiện tại
    ]);

    $this->assertFalse($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_fails_if_today_is_excluded_date()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'excluded_dates' => json_encode([now()->toDateString()]), // Hôm nay bị loại trừ
    ]);

    $this->assertFalse($this->voucherService->isValid($voucher, 200));
  }
  #[Test]
  public function it_checks_if_voucher_is_valid_when_not_excluded_today()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'excluded_dates' => json_encode([now()->addDays(1)->toDateString()]), // Ngày mai bị loại trừ
    ]);

    $this->assertTrue($this->voucherService->isValid($voucher, 200));
  }

  #[Test]
  public function it_excludes_vouchers_exceeding_per_customer_limit()
  {
    $customer = Customer::factory()->create();
    $voucher = Voucher::factory()->create([
      'code' => 'LIMITED_USE',
      'is_active' => true,
      'per_customer_limit' => 3,
    ]);

    // Khách hàng đã sử dụng voucher 3 lần (đạt giới hạn)
    VoucherUsage::factory()->count(3)->create([
      'voucher_id' => $voucher->id,
      'customer_id' => $customer->id,
    ]);

    $isValid = $this->voucherService->isValid($voucher, 500000, $customer->id);
    $this->assertFalse($isValid);
  }
  #[Test]
  public function it_excludes_vouchers_exceeding_per_customer_daily_limit()
  {
    $customer = Customer::factory()->create();
    $voucher = Voucher::factory()->create([
      'code' => 'DAILY_LIMIT',
      'is_active' => true,
      'per_customer_daily_limit' => 2,
    ]);

    // Giả lập khách hàng đã dùng voucher 2 lần trong ngày
    VoucherUsage::factory()->count(2)->create([
      'voucher_id' => $voucher->id,
      'customer_id' => $customer->id,
      'used_at' => Carbon::now(),
    ]);

    $isValid = $this->voucherService->isValid($voucher, 500000, $customer->id);
    $this->assertFalse($isValid);
  }
  #[Test]
  public function it_allows_voucher_usage_if_within_per_customer_daily_limit()
  {
    $customer = Customer::factory()->create();
    $voucher = Voucher::factory()->create([
      'code' => 'DAILY_OK',
      'is_active' => true,
      'per_customer_daily_limit' => 3,
    ]);

    // Khách hàng mới chỉ dùng 2 lần hôm nay, vẫn hợp lệ
    VoucherUsage::factory()->count(2)->create([
      'voucher_id' => $voucher->id,
      'customer_id' => $customer->id,
      'used_at' => Carbon::now(),
    ]);

    $isValid = $this->voucherService->isValid($voucher, 500000, $customer->id);
    $this->assertTrue($isValid);
  }
  #[Test]
  public function it_excludes_voucher_if_per_customer_limit_exists_but_customer_is_null()
  {
    $voucher = Voucher::factory()->create([
      'code' => 'CUSTOMER_LIMIT',
      'is_active' => true,
      'per_customer_limit' => 3,
    ]);

    $isValid = $this->voucherService->isValid($voucher, 500000, null);
    $this->assertFalse($isValid);
  }
  #[Test]
  public function it_excludes_voucher_if_per_customer_daily_limit_exists_but_customer_is_null()
  {
    $voucher = Voucher::factory()->create([
      'code' => 'DAILY_CUSTOMER_LIMIT',
      'is_active' => true,
      'per_customer_daily_limit' => 2,
    ]);

    $isValid = $this->voucherService->isValid($voucher, 500000, null);
    $this->assertFalse($isValid);
  }
  #[Test]
  public function it_returns_valid_vouchers_excluding_those_exceeding_daily_limits()
  {
    $customer = Customer::factory()->create();

    $validVoucher = Voucher::factory()->create([
      'code' => 'VALID',
      'is_active' => true,
      'per_customer_daily_limit' => 2,
    ]);
    $validVoucher2 = Voucher::factory()->create([
      'code' => 'VALID2',
      'is_active' => true,
    ]);

    $exceededDailyLimitVoucher = Voucher::factory()->create([
      'code' => 'DAILY_EXCEEDED',
      'is_active' => true,
      'per_customer_daily_limit' => 5,
    ]);

    // Khách hàng đã dùng "DAILY_EXCEEDED" 2 lần, đạt giới hạn
    VoucherUsage::factory()->count(5)->create([
      'voucher_id' => $exceededDailyLimitVoucher->id,
      'customer_id' => $customer->id,
      'used_at' => Carbon::now(),
    ]);

    $validVouchers = $this->voucherService->getValidVouchers($customer->id);
    $this->assertTrue($validVouchers->contains($validVoucher));
    $this->assertTrue($validVouchers->contains($validVoucher2));
    $this->assertFalse($validVouchers->contains($exceededDailyLimitVoucher));
  }

  #[Test]
  public function it_returns_error_if_voucher_cannot_be_restored()
  {
    $transaction = Mockery::mock(\App\Contracts\VoucherApplicable::class);
    $transaction->shouldReceive('canNotRestoreVoucher')->once()->andReturn(true);
    $transaction->shouldReceive('getMsgVoucherCanNotRestore')->once()->andReturn(Msg::VOUCHER_CANNOT_RESTORE_FROM_INVOICE);

    $result = $this->voucherService->restoreVoucherUsage($transaction);

    $this->assertFalse($result['success']);
    $this->assertEquals(Msg::VOUCHER_CANNOT_RESTORE_FROM_INVOICE, $result['message']);
  }

  #[Test]
  public function it_returns_error_if_voucher_usage_not_found()
  {
    $transaction = Mockery::mock(\App\Contracts\VoucherApplicable::class);
    $transaction->shouldReceive('canNotRestoreVoucher')->once()->andReturn(false);
    $transaction->shouldReceive('getSourceIdField')->once()->andReturn('order_id');
    $transaction->shouldReceive('getTransactionId')->once()->andReturn(999);
    $transaction->shouldReceive('getMsgVoucherNotFound')->once()->andReturn(Msg::VOUCHER_RESTORE_NOT_FOUND);

    $result = $this->voucherService->restoreVoucherUsage($transaction);

    $this->assertFalse($result['success']);
    $this->assertEquals(Msg::VOUCHER_RESTORE_NOT_FOUND, $result['message']);
  }

  #[Test]
  public function it_fails_if_order_completed_or_not_use_voucher()
  {
    $order = Order::factory()->create(['voucher_id' => null]); // Không có voucher

    $result = $this->voucherService->restoreVoucherUsage($order);

    $this->assertFalse($result['success']);
    $this->assertEquals($order->getMsgVoucherCanNotRestore(), $result['message']);
  }

  #[Test]
  public function it_fails_if_order_has_voucher_not_has_into_usage()
  { // Tạo voucher
    $voucher = Voucher::create([
      'code' => 'DISCOUNT50',
      'discount_type' => 'percentage',
      'discount_value' => 50,
      'applied_count' => 1,
      'usage_limit' => 10,
    ]);
    //Tạo order
    $order = Order::factory()->create([
      'order_status'  =>  OrderStatus::PENDING,
      'voucher_id' => $voucher->id,
      'voucher_code' => $voucher->code
    ]);
    //Không tạo VoucherUsage liên quan

    $result = $this->voucherService->restoreVoucherUsage($order);

    $this->assertFalse($result['success']);
    $this->assertEquals($order->getMsgVoucherNotFound(), $result['message']);
  }

  #[Test]
  public function it_successfully_restores_voucher_usage_into_order()
  {
    // Tạo voucher
    $voucher = Voucher::create([
      'code' => 'DISCOUNT50',
      'discount_type' => 'percentage',
      'discount_value' => 50,
      'applied_count' => 1,
      'usage_limit' => 10,
    ]);
    //Tạo order
    $order = Order::factory()->create([
      'order_status'  =>  OrderStatus::PENDING,
      'voucher_id' => $voucher->id,
      'voucher_code' => $voucher->code
    ]);
    // Tạo bản ghi sử dụng voucher
    VoucherUsage::factory()->create([
      'voucher_id' => $voucher->id,
      'order_id' => $order->id,  // Giả định đơn hàng có ID 123
      'discount_amount' => 10000
    ]);

    // Gọi hàm restoreVoucherUsage()
    $result = $this->voucherService->restoreVoucherUsage($order);

    //Làm mới dữ liệu order
    $order->fresh();

    // Kiểm tra kết quả trả về
    $this->assertTrue($result['success']);
    $this->assertEquals(Msg::VOUCHER_RESTORE_SUCCESSFULL, $result['message']);

    // Kiểm tra voucher đã được giảm `applied_count`
    $this->assertEquals(0, $voucher->fresh()->applied_count);

    // Kiểm tra bản ghi `VoucherUsage` đã bị xóa
    $this->assertDatabaseMissing('voucher_usages', [
      'voucher_id' => $voucher->id,
      'order_id' => $order->id,
    ]);

    //Kiểm tra voucher_id đã được xóa trong order
    $this->assertNull($order->voucher_id);

    //Đảm bảo mã voucher vẫn được giữ lại để dễ ra soát
    $this->assertEquals($voucher->code, $order->voucher_code);
  }

  #[Test]
  public function it_rollback_order_and_voucher_if_transaction_faild()
  {
    DB::shouldReceive('transaction')->once()->andThrow(new \Exception('Fake error'));

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Fake error');


    // Tạo voucher
    $voucher = Voucher::create([
      'code' => 'DISCOUNT50',
      'discount_type' => 'percentage',
      'discount_value' => 50,
      'applied_count' => 1,
      'usage_limit' => 10,
    ]);
    //Tạo order
    $order = Order::factory()->create([
      'order_status'  =>  OrderStatus::PENDING,
      'voucher_id' => $voucher->id,
      'voucher_code' => $voucher->code
    ]);
    // Tạo bản ghi sử dụng voucher
    VoucherUsage::factory()->create([
      'voucher_id' => $voucher->id,
      'order_id' => $order->id,  // Giả định đơn hàng có ID 123
      'discount_amount' => 10000
    ]);

    // Gọi hàm restoreVoucherUsage()
    $result = $this->voucherService->restoreVoucherUsage($order);

    //Làm mới dữ liệu order
    $order->fresh();

    // Kiểm tra voucher không giảm `applied_count`
    $this->assertEquals(1, $voucher->fresh()->applied_count);

    // Kiểm tra bản ghi `VoucherUsage` không bị xóa
    $this->assertDatabaseHas('voucher_usages', [
      'voucher_id' => $voucher->id,
      'order_id' => $order->id,
    ]);

    //Kiểm tra voucher_id vẫn được giữ trong order
    $this->assertEquals($voucher->id, $order->voucher_id);

    //Đảm bảo mã voucher vẫn được giữ lại
    $this->assertEquals($voucher->code, $order->voucher_code);
  }
}
