<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
  /**
   * Lấy giá trị cài đặt từ database, có cache để giảm truy vấn DB
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function get(string $key, $default = null)
  {
    return Cache::remember("system_setting_{$key}", now()->addMinutes(10), function () use ($key, $default) {
      return SystemSetting::getValue($key, $default);
    });
  }

  /**
   * Cập nhật hoặc tạo mới giá trị cài đặt, đảm bảo xoá cache sau khi cập nhật
   *
   * @param string $key
   * @param mixed $value
   * @return SystemSetting
   */
  public function set(string $key, $value)
  {
    // Xóa cache trước khi cập nhật database
    Cache::forget("system_setting_{$key}");

    // Cập nhật hoặc tạo mới giá trị trong database
    $setting = SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);

    // Lưu vào cache ngay sau khi cập nhật
    Cache::put("system_setting_{$key}", $value, now()->addMinutes(10));

    return $setting;
  }

  /**
   * Lấy tỷ lệ quy đổi từ số tiền tiêu dùng sang điểm tích luỹ (mặc định: 25,000 VND = 1 điểm)
   *
   * @return float
   */
  public function getPointConversionRate(): float
  {
    return floatval($this->get('point_conversion_rate', 25000));
  }

  /**
   * Lấy tỷ lệ quy đổi điểm thưởng thành tiền (mặc định: 1,000 VND = 1 điểm)
   *
   * @return float
   */
  public function getRewardPointConversionRate(): float
  {
    return floatval($this->get('reward_point_conversion_rate', 1000));
  }

  /**
   * Lấy phần trăm thuế VAT (mặc định: 10%)
   *
   * @return float
   */
  public function getTaxRate(): float
  {
    return floatval($this->get('tax_rate', 10));
  }
}
