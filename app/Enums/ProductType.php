<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum ProductType: string
{
  case GOODS = 'goods';
  case PROCESSED = 'processed';
  case SERVICE = 'service';
  case COMBO = 'combo';

  public function getLabel(): string
  {
    return match ($this) {
      self::GOODS => 'Hoàng hóa',
      self::PROCESSED => 'Hàng chế biến',
      self::SERVICE => 'Dịch vụ',
      self::COMBO => 'Combo',
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
