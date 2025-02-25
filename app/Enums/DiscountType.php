<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum DiscountType: string
{
  case FIXED = 'fixed';
  case PERCENTAGE = 'percentage';

  public function getLabel(): string
  {
    return match ($this) {
      self::FIXED => 'Cố định',
      self::PERCENTAGE => 'Theo %',
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
