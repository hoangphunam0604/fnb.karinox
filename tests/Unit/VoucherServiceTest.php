<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $voucherService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->voucherService = new VoucherService();
  }

  /**
   * Test tạo voucher mới
   */
  public function test_create_voucher()
  {
    $voucherData = [
      'code' => 'DISCOUNT10',
      'discount_type' => 'percentage',
      'discount_value' => 10,
      'min_order_value' => 100,
      'max_discount' => 50,
      'usage_limit' => 100,
      'expires_at' => now()->addDays(30),
      'status' => 'active',
    ];

    $voucher = $this->voucherService->saveVoucher($voucherData);

    $this->assertDatabaseHas('vouchers', ['code' => 'DISCOUNT10']);
    $this->assertInstanceOf(Voucher::class, $voucher);
  }

  /**
   * Test cập nhật voucher
   */
  public function test_update_voucher()
  {
    $voucher = Voucher::factory()->create();

    $updatedData = ['discount_value' => 20, 'status' => 'inactive'];
    $updatedVoucher = $this->voucherService->saveVoucher($updatedData, $voucher->id);

    $this->assertEquals(20, $updatedVoucher->discount_value);
    $this->assertEquals('inactive', $updatedVoucher->status);
    $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'discount_value' => 20, 'status' => 'inactive']);
  }

  /**
   * Test tìm kiếm voucher theo mã
   */
  public function test_find_voucher_by_code()
  {
    $voucher = Voucher::factory()->create(['code' => 'FREESHIP']);

    $foundVoucher = $this->voucherService->findVoucher('FREESHIP');

    $this->assertNotNull($foundVoucher);
    $this->assertEquals('FREESHIP', $foundVoucher->code);
  }

  /**
   * Test xóa voucher
   */
  public function test_delete_voucher()
  {
    $voucher = Voucher::factory()->create();

    $this->voucherService->deleteVoucher($voucher->id);

    $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
  }

  /**
   * Test kiểm tra voucher hợp lệ
   */
  public function test_check_valid_voucher()
  {
    $voucher = Voucher::factory()->create(['code' => 'SALE50', 'expires_at' => now()->addDays(10)]);

    $this->assertTrue($this->voucherService->isValidVoucher('SALE50'));
  }

  /**
   * Test sử dụng voucher (áp dụng)
   */
  public function test_apply_voucher()
  {
    $voucher = Voucher::factory()->create(['code' => 'FREESHIP', 'usage_limit' => 5, 'used_count' => 2]);

    $appliedVoucher = $this->voucherService->applyVoucher('FREESHIP');

    $this->assertEquals(3, $appliedVoucher->used_count);
    $this->assertDatabaseHas('vouchers', ['code' => 'FREESHIP', 'used_count' => 3]);
  }
}
