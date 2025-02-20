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
  public function it_fetches_value_from_cache_if_available()
  {
    // Đặt sẵn dữ liệu trong cache
    Cache::put('system_setting_test_key', 'cached_value', now()->addMinutes(10));

    // Gọi phương thức get(), nó phải lấy từ cache thay vì database
    $result = $this->systemSettingService->get('test_key', 'default_value');

    // Kiểm tra giá trị trả về từ cache
    $this->assertEquals('cached_value', $result);
  }

  /**
   * @testdox Lấy giá trị từ database nếu cache không có dữ liệu
   * @test
   */
  public function it_fetches_value_from_database_if_not_in_cache()
  {
    // Xóa cache trước để đảm bảo nó không có dữ liệu
    Cache::flush();

    // Tạo giá trị trong database
    SystemSetting::updateOrCreate([
      'key' => 'test_key_not_in_cache',
      'value' => 'db_value',
    ]);

    // Kiểm tra nếu cache không có, nó sẽ truy vấn từ database
    $result = $this->systemSettingService->get('test_key_not_in_cache', 'default_value');

    // Kiểm tra giá trị trả về từ database
    $this->assertEquals('db_value', $result);

    // Kiểm tra xem giá trị có được cache hay không
    $this->assertTrue(Cache::has('system_setting_test_key_not_in_cache'));
    $this->assertEquals('db_value', Cache::get('system_setting_test_key_not_in_cache'));
  }


  /**
   * @testdox Trả về giá trị mặc định nếu không được cache và không có trong database
   * @test
   */
  public function it_returns_default_value_if_not_in_cache_and_database()
  {
    // Xóa cache trước để đảm bảo nó không có dữ liệu
    Cache::flush();

    // Gọi phương thức get(), nhưng không có dữ liệu trong cache và database
    $result = $this->systemSettingService->get('non_existing_key', 'default_value');

    // Kiểm tra giá trị trả về là default
    $this->assertEquals('default_value', $result);
  }

  /**
   * @testdox Cập nhật giá trị và xóa cache sau khi cập nhật
   * @test
   */
  public function it_sets_value_and_clears_cache()
  {
    // Đảm bảo có gọi xóa cache
    Cache::shouldReceive('forget')
      ->once()
      ->with('system_setting_test_key_set_value');
    // Đảm bảo có gọi gọi lưu cache 
    Cache::shouldReceive('put')
      ->once()
      ->with('system_setting_test_key_set_value', 'new_value', Mockery::any());

    //Đảm bảo có gọi updateOrCreate
    $mockSystemSetting = Mockery::mock(SystemSetting::class);
    $mockSystemSetting->shouldReceive('updateOrCreate')
      ->once()
      ->with(['key' => 'test_key_set_value'], ['value' => 'new_value'])
      ->andReturnSelf();


    $result = $this->systemSettingService->set('test_key_set_value', 'new_value');

    // Kiểm tra giá trị trả về là giá trị vừa cập nhật
    $this->assertEquals('test_key_set_value', $result->key);
    $this->assertEquals('new_value', $result->value);
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
      ->andReturn(25000);

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
