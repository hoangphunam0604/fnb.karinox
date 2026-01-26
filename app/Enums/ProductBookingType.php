<?php

namespace App\Enums;

enum ProductBookingType: string
{
  case NONE = 'none';
  case PICKLEBALL = 'pickleball';
  case PICKLEBALL_FIXED = 'pickleball_fixed';
  case PICKLEBALL_MEMBER = 'pickleball_member';
  case PICKLEBALL_VIP = 'pickleball_vip';

  public function label(): string
  {
    return match ($this) {
      self::NONE => 'Dịch vụ thường',
      self::PICKLEBALL => 'Dịch vụ sân pickleball',
      self::PICKLEBALL_FIXED => 'Thuê sân pickleball cố định',
      self::PICKLEBALL_MEMBER => 'Hội viên pickleball',
      self::PICKLEBALL_VIP => 'Hội viên VIP pickleball',
    };
  }

  public static function options(): array
  {
    return array_map(
      fn($case) => [
        'value' => $case->value,
        'label' => $case->label()
      ],
      self::cases()
    );
  }
}
