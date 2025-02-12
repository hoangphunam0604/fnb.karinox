<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Models\Order;
use App\Models\Voucher;
use App\Services\OrderService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class VoucherServiceTest extends TestCase
{
  use RefreshDatabase; // Reset database trước mỗi test

  protected $voucherService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->voucherService = new VoucherService();
  }

  /** @test */
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

  /** @test */
  public function it_can_update_a_voucher()
  {
    $voucher = Voucher::factory()->create(['code' => 'UPDATE50']);
    $updatedData = ['discount_value' => 20];

    $this->voucherService->update($voucher, $updatedData);

    $this->assertDatabaseHas('vouchers', ['code' => 'UPDATE50', 'discount_value' => 20]);
  }

  /** @test */
  public function it_can_delete_a_voucher()
  {
    $voucher = Voucher::factory()->create();
    $this->voucherService->delete($voucher);

    $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
  }

  /** @test */
  public function it_can_find_voucher_by_code()
  {
    $voucher = Voucher::factory()->create(['code' => 'FINDME']);

    $foundVoucher = $this->voucherService->findByCode('FINDME');

    $this->assertNotNull($foundVoucher);
    $this->assertEquals('FINDME', $foundVoucher->code);
  }

  /** @test */
  public function it_can_get_paginated_vouchers()
  {
    Voucher::factory()->count(15)->create();

    $paginatedVouchers = $this->voucherService->getAllPaginated(10);

    $this->assertCount(10, $paginatedVouchers); // Kiểm tra có 10 voucher được lấy ra
  }

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

    $result = $this->voucherService->applyVoucher($voucher, $order);

    $this->assertTrue($result['success']);
    $this->assertEquals(20, $result['discount']);
    $this->assertEquals(180, $result['final_total']);

    $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'applied_count' => 6]);
  }

  /** @test */
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

  /** @test */
  public function it_checks_voucher_valid_for_membership_level()
  {
    $membershipLevel = MembershipLevel::factory()->create(['id' => 1, 'name' => "Bronze"]);
    $customer = Customer::factory()->create(['membership_level_id' => $membershipLevel->id]);
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'applicable_membership_levels' => json_encode([$membershipLevel->id, 2]),
    ]);
    $result = $this->voucherService->isValid($voucher, 200, $customer->id);
    $this->assertTrue($result); // Không kiểm tra hạng thành viên
  }

  /** @test */
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

  /** @test */
  public function it_checks_voucher_valid_for_current_day()
  {
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'start_date' => now()->subDay(),
      'end_date' => now()->addDays(10),
      'valid_days_of_week' => json_encode([now()->dayOfWeek]), // Chỉ hợp lệ hôm nay
    ]);

    $this->assertTrue($this->voucherService->isValid($voucher, 200));
  }

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
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

  /** @test */
  public function it_does_not_refund_voucher_if_not_used()
  {
    // Tạo voucher nhưng chưa áp dụng
    $voucher = Voucher::factory()->create(['is_active' => true]);

    // Tạo đơn hàng nhưng chưa dùng voucher
    $order = Order::factory()->create(['order_status' => 'confirmed']);

    // Cố gắng hoàn lại voucher
    $result = $this->voucherService->refundVoucher($order);

    // Kiểm tra thông báo lỗi
    $this->assertFalse($result['success']);
    $this->assertEquals('Không tìm thấy voucher để hoàn lại.', $result['message']);
  }

  /** @test */
  public function it_does_not_refund_voucher_if_order_already_completed()
  {
    // Tạo voucher
    $voucher = Voucher::factory()->create([
      'is_active' => true,
      'discount_type' => 'percentage',
      'discount_value' => 10,
      'usage_limit' => 10,
      'applied_count' => 2,
    ]);

    $customer = Customer::factory()->create();
    // Tạo đơn hàng đã hoàn thành (completed)
    $order = Order::factory()->create(['customer_id' => null, 'order_status' => 'completed', 'total_price' => 200]);

    // Áp dụng voucher
    $this->voucherService->applyVoucher($voucher, $order);

    // Cố gắng hoàn lại voucher (đơn đã hoàn tất)
    $result = $this->voucherService->refundVoucher($order);

    // Kiểm tra rằng voucher không được hoàn lại
    $this->assertFalse($result['success']);
    $this->assertEquals('Không thể hoàn lại voucher vì đơn hàng đã hoàn tất.', $result['message']);
  }
}
