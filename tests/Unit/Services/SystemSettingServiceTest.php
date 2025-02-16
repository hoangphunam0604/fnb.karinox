<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingServiceTest extends TestCase
{
  use RefreshDatabase;

  protected SystemSettingService $systemSettingService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->systemSettingService = new SystemSettingService();
  }

  /**
   * @testdox Lấy giá trị của một cài đặt hệ thống nếu tồn tại
   * @test
   */
  public function it_can_get_a_setting_value()
  {
    SystemSetting::create([
      'key' => 'site_name',
      'value' => 'Karinox Coffee'
    ]);

    $this->assertEquals('Karinox Coffee', $this->systemSettingService->get('site_name'));
  }

  /**
   * @testdox Trả về giá trị mặc định nếu cài đặt hệ thống không tồn tại
   * @test
   */
  public function it_returns_default_value_if_setting_not_found()
  {
    $this->assertEquals('default_value', $this->systemSettingService->get('non_existing_key', 'default_value'));
  }

  /**
   * @testdox Tạo một cài đặt hệ thống mới nếu chưa tồn tại
   * @test
   */
  public function it_can_set_a_new_system_setting()
  {
    $this->systemSettingService->set('currency', 'VND');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'currency',
      'value' => 'VND'
    ]);
  }

  /**
   * @testdox Cập nhật một cài đặt hệ thống hiện có
   * @test
   */
  public function it_can_update_an_existing_system_setting()
  {
    SystemSetting::create([
      'key' => 'timezone',
      'value' => 'UTC'
    ]);

    $this->systemSettingService->set('timezone', 'Asia/Ho_Chi_Minh');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'timezone',
      'value' => 'Asia/Ho_Chi_Minh'
    ]);
  }

  /**
   * @testdox Tạo mới hoặc cập nhật một cài đặt hệ thống
   * @test
   */
  public function it_can_create_or_update_system_setting()
  {
    $this->systemSettingService->set('tax_rate', '10%');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'tax_rate',
      'value' => '10%'
    ]);

    $this->systemSettingService->set('tax_rate', '12%');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'tax_rate',
      'value' => '12%'
    ]);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm mặc định (25,000) khi không có giá trị trong hệ thống
   * @test
   */
  public function it_returns_default_point_conversion_rate_when_not_set()
  {
    // Không có giá trị trong DB => phải trả về mặc định 25000
    $rate = $this->systemSettingService->getPointConversionRate();
    $this->assertEquals(25000, $rate);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm tùy chỉnh khi có giá trị trong hệ thống
   * @test
   */
  public function it_returns_custom_point_conversion_rate_when_set()
  {
    // Tạo giá trị trong DB
    SystemSetting::create([
      'key' => 'point_conversion_rate',
      'value' => 30000,
    ]);

    // Phải trả về giá trị đã lưu (30000)
    $rate = $this->systemSettingService->getPointConversionRate();
    $this->assertEquals(30000, $rate);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm thưởng mặc định (1,000) khi không có giá trị trong hệ thống
   * @test
   */
  public function it_returns_default_reward_point_conversion_rate_when_not_set()
  {
    // Không có giá trị trong DB => phải trả về mặc định 1000
    $rate = $this->systemSettingService->getRewardPointConversionRate();
    $this->assertEquals(1000, $rate);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm thưởng tùy chỉnh khi có giá trị trong hệ thống
   * @test
   */
  public function it_returns_custom_reward_point_conversion_rate_when_set()
  {
    // Tạo giá trị trong DB
    SystemSetting::create([
      'key' => 'reward_point_conversion_rate',
      'value' => 2000,
    ]);

    // Phải trả về giá trị đã lưu (2000)
    $rate = $this->systemSettingService->getRewardPointConversionRate();
    $this->assertEquals(2000, $rate);
  }
}
