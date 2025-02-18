<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class SystemSettingServiceTest extends TestCase
{
  protected $systemSettingService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->systemSettingService = new SystemSettingService();
  }

  /**
   * @testdox Lấy giá trị từ cache nếu cache đã có dữ liệu
   * @test
   */
  public function it_returns_value_from_cache_if_exists()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_test_key', Mockery::any(), Mockery::any())
      ->andReturn('cached_value');

    $result = $this->systemSettingService->get('test_key', 'default_value');

    $this->assertEquals('cached_value', $result);
  }

  /**
   * @testdox Lấy giá trị từ database nếu cache không có dữ liệu
   * @test
   */
  public function it_fetches_value_from_database_if_not_in_cache()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_test_key', Mockery::any(), Mockery::on(function ($closure) {
        SystemSetting::shouldReceive('getValue')
          ->once()
          ->with('test_key', 'default_value')
          ->andReturn('db_value');
        return true;
      }))
      ->andReturn('db_value');

    $result = $this->systemSettingService->get('test_key', 'default_value');

    $this->assertEquals('db_value', $result);
  }

  /**
   * @testdox Cập nhật giá trị và xóa cache sau khi cập nhật
   * @test
   */
  public function it_sets_value_and_clears_cache()
  {
    Cache::shouldReceive('forget')
      ->once()
      ->with('system_setting_test_key');

    Cache::shouldReceive('put')
      ->once()
      ->with('system_setting_test_key', 'new_value', Mockery::any());

    SystemSetting::shouldReceive('updateOrCreate')
      ->once()
      ->with(['key' => 'test_key'], ['value' => 'new_value'])
      ->andReturnSelf();

    $this->systemSettingService->set('test_key', 'new_value');
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm tích luỹ mặc định nếu chưa có thiết lập
   * @test
   */
  public function it_returns_default_point_conversion_rate_if_not_set()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_point_conversion_rate', Mockery::any(), Mockery::any())
      ->andReturn(null);

    $result = $this->systemSettingService->getPointConversionRate();

    $this->assertEquals(25000, $result);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm tích luỹ đã được thiết lập
   * @test
   */
  public function it_returns_saved_point_conversion_rate()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_point_conversion_rate', Mockery::any(), Mockery::any())
      ->andReturn(30000);

    $result = $this->systemSettingService->getPointConversionRate();

    $this->assertEquals(30000, $result);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm thưởng thành tiền mặc định nếu chưa có thiết lập
   * @test
   */
  public function it_returns_default_reward_point_conversion_rate_if_not_set()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_reward_point_conversion_rate', Mockery::any(), Mockery::any())
      ->andReturn(null);

    $result = $this->systemSettingService->getRewardPointConversionRate();

    $this->assertEquals(1000, $result);
  }

  /**
   * @testdox Trả về tỷ lệ quy đổi điểm thưởng thành tiền đã được thiết lập
   * @test
   */
  public function it_returns_saved_reward_point_conversion_rate()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_reward_point_conversion_rate', Mockery::any(), Mockery::any())
      ->andReturn(1500);

    $result = $this->systemSettingService->getRewardPointConversionRate();

    $this->assertEquals(1500, $result);
  }

  /**
   * @testdox Trả về phần trăm thuế mặc định nếu chưa có thiết lập
   * @test
   */
  public function it_returns_default_tax_rate_if_not_set()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_tax_rate', Mockery::any(), Mockery::any())
      ->andReturn(null);

    $result = $this->systemSettingService->getTaxRate();

    $this->assertEquals(10, $result);
  }

  /**
   * @testdox Trả về phần trăm thuế đã được thiết lập
   * @test
   */
  public function it_returns_saved_tax_rate()
  {
    Cache::shouldReceive('remember')
      ->once()
      ->with('system_setting_tax_rate', Mockery::any(), Mockery::any())
      ->andReturn(8);

    $result = $this->systemSettingService->getTaxRate();

    $this->assertEquals(8, $result);
  }
}
