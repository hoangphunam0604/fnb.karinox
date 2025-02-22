<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum OrderItemStatus: string
{
  case PENDING = 'pending';       // Chờ xác nhận
  case ACCEPTED = 'accepted';     // Đã xác nhận, chờ chế biến
  case PREPARING = 'preparing';   // Đang chế biến
  case PREPARED = 'prepared';     // Đã chế biến xong
  case SERVING = 'serving';       // Đang phục vụ
  case SERVED = 'served';         // Đã phục vụ
  case CANCELED = 'canceled';     // Đã hủy trước khi chế biến
  case REFUNDED = 'refunded';     // Hoàn tiền sau khi phục vụ

  /**
   * Lấy mô tả cho từng trạng thái
   */
  public function description(): string
  {
    return match ($this) {
      self::PENDING   => 'Món vừa được đặt, chưa gửi vào bếp.',
      self::ACCEPTED  => 'Nhân viên đã xác nhận đơn hàng.',
      self::PREPARING => 'Bếp đang chế biến món ăn.',
      self::PREPARED  => 'Món đã nấu xong, sẵn sàng phục vụ.',
      self::SERVING   => 'Nhân viên đang mang món đến cho khách.',
      self::SERVED    => 'Khách đã nhận món, hoàn tất.',
      self::CANCELED  => 'Món đã bị hủy trước khi chế biến.',
      self::REFUNDED  => 'Món bị hoàn tiền sau khi phục vụ.',
    };
  }

  /**
   * Lấy danh sách tất cả trạng thái dưới dạng mảng key-value
   */
  public static function all(): array
  {
    return array_column(self::cases(), 'value');
  }

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
}
