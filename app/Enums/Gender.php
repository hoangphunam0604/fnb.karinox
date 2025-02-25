<?php

namespace App\Enums;

enum Gender: string
{
  case MALE = 'male';
  case FEMALE = 'female';

  /**
   * Kiểm tra xem có phải giới tính nam không.
   */
  public function isMale(): bool
  {
    return $this === self::MALE;
  }

  /**
   * Kiểm tra xem có phải giới tính nữ không.
   */
  public function isFemale(): bool
  {
    return $this === self::FEMALE;
  }
}
