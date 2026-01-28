<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum ProductArenaType: string
{
  case NONE = 'none';
  case SOCIAL_SLOT = 'social_slot';
  case FULL_SLOT = 'full_slot';
  case ARENA_MEMBER = 'member';
  case ARENA_VIP = 'vip';

  public function getLabel(): string
  {
    return match ($this) {
      self::NONE => 'Sản phẩm thường',
      self::SOCIAL_SLOT => 'Đặt chỗ social',
      self::FULL_SLOT => 'Đặt sân full',
      self::ARENA_MEMBER => 'Gói hội viên arena',
      self::ARENA_VIP => 'Gói hội viên VIP',
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
