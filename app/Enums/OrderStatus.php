<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum OrderStatus: string
{
  case PENDING = 'pending';
  case CONFIRMED = 'confirmed';
  case COMPLETED = 'completed';
  case CANCELED = 'canceled';

  // Hàm kiểm tra trạng thái hợp lệ
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
