<?php

namespace App\Enums;

enum ProductBookingType: string
{
  case NONE = 'none';
  case PICKLEBALL = 'pickleball';
  case PICKLEBALL_FIXED = 'pickleball_fixed';

  public function label(): string
  {
    return match ($this) {
      self::NONE => 'Dịch vụ thường',
      self::PICKLEBALL => 'Dịch vụ sân pickleball',
      self::PICKLEBALL_FIXED => 'Thuê sân pickleball cố định',
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
