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
}
