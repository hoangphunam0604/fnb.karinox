<?php

namespace App\Services;

use App\Models\SystemSetting;

class SystemSettingService
{
  public function get(string $key, $default = null)
  {
    return SystemSetting::getValue($key, $default);
  }

  public function set(string $key, $value)
  {
    return SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
  }
  // Lấy tỷ lệ quy đổi điểm từ SystemSetting mặc định là 25.000 một điểm
  public function getPointConversionRate(): float
  {
    return floatval(SystemSetting::where('key', 'point_conversion_rate')->value('value') ?? 25000);
  }
}
