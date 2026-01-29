<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum ProductArenaType: string
{
  case NONE = 'none';
  case FULL_SLOT = 'full_slot';
  case SOCIAL_SLOT = 'social_slot';
  case SOCIAL_MEMBER = 'social_member';
  case ARENA_MEMBER = 'arena_member';
  case ARENA_MORNING_MEMBER = 'arena_morning_member';
  case ARENA_RELAX_MEMBER = 'arena_relax_member';
  case ARENA_VIP = 'arena_vip';
  case ARENA_VVIP = 'arena_vvip';
  case ARENA_MASTER = 'arena_master';

  public function getLabel(): string
  {
    return match ($this) {
      self::NONE => 'Sản phẩm thường',
      self::FULL_SLOT => 'Đặt sân full',

      self::SOCIAL_SLOT => 'Đặt chỗ social',
      self::SOCIAL_MEMBER => 'SOCIAL MEMBER',

      self::ARENA_MEMBER => 'MEMBER',
      self::ARENA_MORNING_MEMBER => 'MORNING MEMBER',
      self::ARENA_RELAX_MEMBER => 'RELAX MEMBER',
      self::ARENA_VIP => 'VIP',
      self::ARENA_VVIP => 'VVIP',
      self::ARENA_MASTER => 'MASTER',
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
