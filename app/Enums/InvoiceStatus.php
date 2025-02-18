<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum InvoiceStatus: string
{
  case PENDING = 'pending';
  case CANCELED = 'canceled';
  case COMPLETED = 'completed';

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
