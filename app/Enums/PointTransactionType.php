<?php

namespace App\Enums;


enum PointStatusType: string
{
  case EARN = 'earn';       // Tích lũy điểm
  case REDEEM = 'redeem';   // Sử dụng điểm

  public function getLabel(): string
  {
    return match ($this) {
      self::EARN => "Cộng điểm",
      self::REDEEM => "Trừ điểm",
    };
  }

  /**
   * Kiểm tra xem đây có phải là cộng điểm không.
   */
  public function isEarn(): bool
  {
    return $this === self::EARN;
  }

  /**
   * Kiểm tra xem đây có phải là trừ điểm không.
   */
  public function isRedeem(): bool
  {
    return $this === self::REDEEM;
  }
}
