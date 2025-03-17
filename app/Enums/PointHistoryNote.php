<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum PointHistoryNote: string
{
  case ORDER_USER_REWARD_POINTS = 'Sử dụng điểm để đặt hàn';
  case ORDER_RESTORE_REWARD_POINTS = 'Khôi phục điểm khi huỷ đơn đặt hàng';

  case INVOICE_USER_REWARD_POINTS = 'Sử dụng điểm thanh toán';
  case INVOICE_RESTORE_REWARD_POINTS = 'Khôi phục điểm khi huỷ hoá đơn';



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
