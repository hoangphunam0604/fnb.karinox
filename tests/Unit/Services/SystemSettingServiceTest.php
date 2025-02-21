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


  public function it_fetches_value_from_cache_if_available()
  {
    // Đặt sẵn dữ liệu trong cache
    Cache::put('system_setting_test_key', 'cached_value', now()->addMinutes(10));

    // Gọi phương thức get(), nó phải lấy từ cache thay vì database
    $result = $this->systemSettingService->get('test_key', 'default_value');

    // Kiểm tra giá trị trả về từ cache
    $this->assertEquals('cached_value', $result);
  }


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



  public function it_returns_default_value_if_not_in_cache_and_database()
  {
    // Xóa cache trước để đảm bảo nó không có dữ liệu
    Cache::flush();

    // Gọi phương thức get(), nhưng không có dữ liệu trong cache và database
    $result = $this->systemSettingService->get('non_existing_key', 'default_value');

    // Kiểm tra giá trị trả về là default
    $this->assertEquals('default_value', $result);
  }


  public function it_sets_value_and_clears_cache()
  {
    // Xóa cache để đảm bảo không có dữ liệu cũ
    Cache::flush();


    // Đặt một giá trị giả vào cache trước để kiểm tra việc xóa
    Cache::put('system_setting_test_key_set_value', 'old_value', now()->addMinutes(10));

    // Gọi phương thức set()
    $result = $this->systemSettingService->set('test_key_set_value', 'new_value');



    // Kiểm tra cache đã lưu giá trị mới
    $this->assertTrue(Cache::has('system_setting_test_key_set_value'));;

    // Kiểm tra dữ liệu trả về từ phương thức set()
    $this->assertInstanceOf(SystemSetting::class, $result);
    $this->assertEquals('test_key_set_value', $result->key);
    $this->assertEquals('new_value', $result->value);

    // Kiểm tra dữ liệu đã thực sự được lưu vào database
    $this->assertDatabaseHas('system_settings', [
      'key' => 'test_key_set_value',
      'value' => 'new_value'
    ]);
  }


  public function it_returns_default_point_conversion_rate_if_not_set()
  {
    // Xóa cache và đảm bảo database không có dữ liệu
    Cache::flush();
    SystemSetting::where('key', 'point_conversion_rate')->delete();
    // Không tạo dữ liệu trong database để kiểm tra giá trị mặc định
    $result = $this->systemSettingService->getPointConversionRate();

    // Kiểm tra giá trị trả về phải là mặc định 25000
    $this->assertEquals(25000, $result);
  }


  public function it_returns_saved_point_conversion_rate()
  {
    // Xóa cache 
    Cache::flush();
    // Tạo dữ liệu được thiết lập trong database
    SystemSetting::updateOrCreate(['key' => 'point_conversion_rate', 'value' => 10000]);

    $result = $this->systemSettingService->getPointConversionRate();

    $this->assertEquals(10000, $result);
  }


  public function it_returns_default_reward_point_conversion_rate_if_not_set()
  {
    // Xóa cache và đảm bảo database không có dữ liệu
    Cache::flush();
    SystemSetting::where('key', 'reward_point_conversion_rate')->delete();
    // Không tạo dữ liệu trong database để kiểm tra giá trị mặc định
    $result = $this->systemSettingService->getRewardPointConversionRate();

    // Kiểm tra giá trị trả về phải là mặc định 1000
    $this->assertEquals(1000, $result);
  }


  public function it_returns_saved_reward_point_conversion_rate()
  {
    // Xóa cache 
    Cache::flush();
    // Tạo dữ liệu được thiết lập trong database
    SystemSetting::updateOrCreate(['key' => 'reward_point_conversion_rate', 'value' => 1500]);

    $result = $this->systemSettingService->getRewardPointConversionRate();

    $this->assertEquals(1500, $result);
  }


  public function it_returns_default_tax_rate_if_not_set()
  {
    // Xóa cache và đảm bảo database không có dữ liệu
    Cache::flush();
    SystemSetting::where('key', 'tax_rate')->delete();
    // Không tạo dữ liệu trong database để kiểm tra giá trị mặc định
    $result = $this->systemSettingService->getTaxRate();

    // Kiểm tra giá trị trả về phải là mặc định 10 (%)
    $this->assertEquals(10, $result);
  }


  public function it_returns_saved_tax_rate()
  {
    // Xóa cache 
    Cache::flush();
    // Tạo dữ liệu được thiết lập trong database
    SystemSetting::updateOrCreate(['key' => 'tax_rate', 'value' => 15]);

    $result = $this->systemSettingService->getTaxRate();

    $this->assertEquals(15, $result);
  }
}
