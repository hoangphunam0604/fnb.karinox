<?php

namespace Tests\Unit\Services;

use App\Enums\Msg;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\CustomerService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;


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
  #[TestDox('Không thể sử dụng voucher khi bị vô hiệu hóa')]
  public function testVoucherIsInactive()
  {
    // Tạo một voucher bị vô hiệu hóa
    $voucher = Voucher::factory()->create([
      'is_active' => false,
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(1),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.inactive_or_expired'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher khi đã hết hạn')]
  public function testVoucherIsExpired()
  {
    // Tạo một voucher đã hết hạn
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => Carbon::now()->subDays(10),
      'end_date' => Carbon::now()->subDays(1), // Voucher đã hết hạn
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.inactive_or_expired'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher khi chưa đến ngày bắt đầu')]
  public function testVoucherNotStartedYet()
  {
    // Tạo một voucher chưa có hiệu lực
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => Carbon::now()->addDays(2), // Chưa đến ngày bắt đầu
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.inactive_or_expired'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu giá trị đơn hàng thấp hơn mức tối thiểu')]
  public function testOrderBelowMinimumValue()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'min_order_value' => 100000, // Giá trị tối thiểu 100k
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1); // Đơn hàng chỉ 50k

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.min_order_value'), $result->message);
  }


  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu giá trị đơn hàng bằng hoặc cao hơn mức tối thiểu')]
  public function testOrderMeetsMinimumValue()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'min_order_value' => 100000, // Giá trị tối thiểu 100k
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $result = $this->voucherService->isValid($voucher, 100000, 1); // Đơn hàng đúng 100k

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher khi đã đạt giới hạn số lần sử dụng')]
  public function testVoucherUsageLimitExceeded()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'usage_limit' => 3, // Giới hạn sử dụng tối đa 3 lần
      'applied_count' => 3, // Đã đạt giới hạn
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.usage_limit_exceeded'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu khách hàng đã đạt giới hạn số lần sử dụng')]
  public function testVoucherPerCustomerLimitExceeded()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'per_customer_limit' => 2, // Mỗi khách hàng chỉ được dùng tối đa 2 lần
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);
    $customer = Customer::factory()->create();

    // Giả lập khách hàng đã sử dụng voucher 2 lần
    VoucherUsage::factory()->count(2)->create([
      'voucher_id' => $voucher->id,
      'customer_id' => $customer->id,
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.per_customer_limit_exceeded'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu khách hàng đã đạt giới hạn số lần sử dụng trong ngày')]
  public function testVoucherPerCustomerDailyLimitExceeded()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'per_customer_daily_limit' => 1, // Mỗi khách hàng chỉ được dùng tối đa 1 lần/ngày
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    // Giả lập khách hàng đã sử dụng voucher hôm nay
    VoucherUsage::factory()->create([
      'voucher_id' => $voucher->id,
      'customer_id' => 1,
      'used_at' => Carbon::now(),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.per_customer_daily_limit_exceeded'), $result->message);
  }


  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu hạng thành viên không hợp lệ')]
  public function testVoucherInvalidMembershipLevel()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'applicable_membership_levels' => json_encode([2, 3]), // Chỉ cho phép hạng 2, 3
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $customerServiceMock = $this->createMock(CustomerService::class);
    $customerServiceMock->method('getCustomerMembershipLevel')->willReturn((object)['id' => 1]); // Hạng 1, không hợp lệ

    $this->voucherService = new VoucherService($customerServiceMock);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.invalid_membership_level'), $result->message);
  }

  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu hạng thành viên hợp lệ')]
  public function testVoucherValidMembershipLevel()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'applicable_membership_levels' => json_encode([2, 3]), // Chỉ cho phép hạng 2, 3
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $customerServiceMock = $this->createMock(CustomerService::class);
    $customerServiceMock->method('getCustomerMembershipLevel')->willReturn((object)['id' => 2]); // Hạng 2, hợp lệ

    $this->voucherService = new VoucherService($customerServiceMock);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu không áp dụng cho ngày trong tuần hiện tại')]
  public function testVoucherInvalidDayOfWeek()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_days_of_week' => json_encode([1, 2, 3]), // Chỉ áp dụng vào Thứ 2, Thứ 3, Thứ 4
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    Carbon::setTestNow(Carbon::now()->next(5)); // Giả lập hôm nay là Thứ 6 (5)

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.invalid_day_of_week'), $result->message);
  }

  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu ngày trong tuần hợp lệ')]
  public function testVoucherValidDayOfWeek()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_days_of_week' => json_encode([1, 2, 3]), // Chỉ áp dụng vào Thứ 2, Thứ 3, Thứ 4
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    Carbon::setTestNow(Carbon::now()->next(2)); // Giả lập hôm nay là Thứ 3 (2)

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
  }


  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu không áp dụng cho tuần hiện tại của tháng')]
  public function testVoucherInvalidWeekOfMonth()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_weeks_of_month' => json_encode([1, 2]), // Chỉ áp dụng cho tuần 1 và 2 của tháng
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    Carbon::setTestNow(Carbon::now()->startOfMonth()->addWeeks(3)); // Giả lập hôm nay là tuần 4

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.invalid_week_of_month'), $result->message);
  }

  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu tuần trong tháng hợp lệ')]
  public function testVoucherValidWeekOfMonth()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_weeks_of_month' => json_encode([1, 2]), // Chỉ áp dụng cho tuần 1 và 2 của tháng
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    Carbon::setTestNow(Carbon::now()->startOfMonth()->addWeek(1)); // Giả lập hôm nay là tuần 2

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu không áp dụng cho tháng hiện tại')]
  public function testVoucherInvalidMonth()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_months' => json_encode([1, 2, 3]), // Chỉ áp dụng cho tháng 1, 2, 3
      'start_date' => Carbon::now()->subMonths(1),
      'end_date' => Carbon::now()->addMonths(1),
    ]);

    Carbon::setTestNow(Carbon::create(null, 5, 1)); // Giả lập hôm nay là tháng 5

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.invalid_month'), $result->message);
  }

  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu tháng hợp lệ')]
  public function testVoucherValidMonth()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_months' => json_encode([1, 2, 3]), // Chỉ áp dụng cho tháng 1, 2, 3
      'start_date' => Carbon::now()->subMonths(1),
      'end_date' => Carbon::now()->addMonths(1),
    ]);

    Carbon::setTestNow(Carbon::create(null, 2, 1)); // Giả lập hôm nay là tháng 2

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
  }
  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu không áp dụng trong khung giờ hiện tại')]
  public function testVoucherInvalidTimeRange()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_time_ranges' => json_encode(['08:00-10:00', '14:00-16:00']), // Chỉ áp dụng từ 08:00-10:00 và 14:00-16:00
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    Carbon::setTestNow(Carbon::today()->setTime(11, 0)); // Giả lập hiện tại là 11:00

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.invalid_time_range'), $result->message);
  }

  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu khung giờ hợp lệ')]
  public function testVoucherValidTimeRange()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'valid_time_ranges' => json_encode(['08:00-10:00', '14:00-16:00']), // Chỉ áp dụng từ 08:00-10:00 và 14:00-16:00
      'start_date' => Carbon::now()->subDays(1),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    Carbon::setTestNow(Carbon::today()->setTime(9, 0)); // Giả lập hiện tại là 09:00

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
  }

  #[Test]
  #[TestDox('Không thể sử dụng voucher nếu ngày hiện tại nằm trong danh sách ngày bị loại trừ')]
  public function testVoucherExcludedDate()
  {
    $excludedDate = Carbon::now()->toDateString(); // Ngày hôm nay

    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'excluded_dates' => json_encode([$excludedDate]), // Ngày hôm nay bị loại trừ
      'start_date' => Carbon::now()->subDays(10),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertFalse($result->success);
    $this->assertSame(config('messages.voucher.excluded_date'), $result->message);
  }

  #[Test]
  #[TestDox('Có thể sử dụng voucher nếu ngày hiện tại không nằm trong danh sách ngày bị loại trừ')]
  public function testVoucherNonExcludedDate()
  {
    $excludedDate = Carbon::now()->addDay()->toDateString(); // Ngày mai bị loại trừ

    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'excluded_dates' => json_encode([$excludedDate]), // Chỉ loại trừ ngày mai
      'start_date' => Carbon::now()->subDays(10),
      'end_date' => Carbon::now()->addDays(10),
    ]);

    $result = $this->voucherService->isValid($voucher, 50000, 1);

    $this->assertTrue($result->success);
    $this->assertSame(config('messages.voucher.valid'), $result->message);
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
