<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum VoucherType: string
{
  case STANDARD = 'standard';
  case MEMBERSHIP = 'membership';

  public function getLabel(): string
  {
    return match ($this) {
      self::STANDARD => 'Voucher chuẩn',
      self::MEMBERSHIP => 'Voucher thành viên',
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
