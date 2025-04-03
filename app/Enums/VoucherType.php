<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum VoucherType: string
{
  case COMMON = 'common';
  case PRIVATE = 'private';

  public function getLabel(): string
  {
    return match ($this) {
      self::COMMON => 'Voucher chung',
      self::PRIVATE => 'Voucher riêng',
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
