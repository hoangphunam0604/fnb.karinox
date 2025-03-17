<?php

namespace App\Enums;


enum CustomerPointType: string
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
   * Kiểm tra xem đây có phải là điểm tích lũy không.
   */
  public function isEarn(): bool
  {
    return $this === self::EARN;
  }

  /**
   * Kiểm tra xem đây có phải là điểm sử dụng không.
   */
  public function isRedeem(): bool
  {
    return $this === self::REDEEM;
  }

  /**
   * Kiểm tra xem đây có phải là điểm hết hạn không.
   */
  public function isExpired(): bool
  {
    return $this === self::EXPIRED;
  }
}
