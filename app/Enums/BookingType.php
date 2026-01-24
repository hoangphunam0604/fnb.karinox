<?php

namespace App\Enums;

enum BookingType: string
{
  case FULL = 'full';
  case SOCIAL = 'social';

  public function label(): string
  {
    return match ($this) {
      self::FULL => 'Bao sân',
      self::SOCIAL => 'Vé lẻ social',
    };
  }
}
