<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum PaymentStatus: string
{
  case UNPAID = 'unpaid';
  case PARTIAL = 'partial';
  case PAID = 'paid';
  case REFUNDED = 'refunded';

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
