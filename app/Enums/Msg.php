<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum Msg: string
{
  case VOUCHER_RESTORE_NOT_FOUND = 'Không tìm thấy voucher để hoàn lại.';

  case VOUCHER_CANNOT_RESTORE_FROM_ORDER = 'Không thể hoàn lại voucher vì đơn hàng đã hoàn tất.';

  case VOUCHER_CANNOT_RESTORE_FROM_INVOICE = 'Không thể hoàn lại voucher vì hoá đơn đã hoàn tất.';

  case VOUCHER_RESTORE_SUCCESSFULL = 'Voucher đã được hoàn lại.';


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
