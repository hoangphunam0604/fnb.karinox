<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum CustomerStatus: string
{
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
  case BANNED = 'banned';

  public function getLabel(): string
  {
    return match ($this) {
      self::ACTIVE => 'Đang hoạt động',
      self::INACTIVE => 'Ngừng hoạt động',
      self::BANNED => 'Đang bị cấm',
    };
  }

  // Kiểm tra trạng thái hợp lệ
  public static function isValid(string $status): bool
  {
    return in_array($status, self::casesAsArray());
  }

  public static function casesAsArray(): array
  {
    return array_column(self::cases(), 'value');
  }

  public static function fake(): self
  {
    return Arr::random(self::cases());
  }
}
