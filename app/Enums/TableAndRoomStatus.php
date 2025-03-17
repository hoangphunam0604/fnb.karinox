<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum TableAndRoomStatus: string
{
  case AVAILABLE = 'available';
  case OCCUPIED = 'occupied';
  case RESERVED = 'reserved';
  case MAINTENANCE = 'maintenance';

  public function getLabel(): string
  {
    return match ($this) {
      self::AVAILABLE => "Còn trống, sẵn sàng sử dụng.",
      self::OCCUPIED => "Đang có khách sử dụng.",
      self::RESERVED => "Đã đặt trước.",
      self::MAINTENANCE => "Đang bảo trì.",
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
