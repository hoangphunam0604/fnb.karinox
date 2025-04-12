<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum KitchenTicketStatus: string
{
  case WAITING = 'waiting';
  case PROCESSING = 'processing';
  case READY = 'ready';
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

  public static function labels(): array
  {
    return [
      self::WAITING->value => 'Chờ xác nhận',
      self::PROCESSING->value => 'Đang chế biến',
      self::COMPLETED->value => 'Hoàn thành',
      self::CANCELED->value => 'Đã huỷ',
    ];
  }

  public function getLabel(): string
  {
    return match ($this) {
      self::WAITING => 'Chờ xác nhận',
      self::PROCESSING => 'Đang chế biến',
      self::COMPLETED => 'Hoàn thành',
      self::CANCELED => 'Đã huỷ',
    };
  }
}
