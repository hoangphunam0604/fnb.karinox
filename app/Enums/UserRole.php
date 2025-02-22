<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum UserRole: string
{
  case ADMIN = 'admin'; // Quản trị viên: Toàn quyền quản lý hệ thống
  case MANAGER = 'manager'; // Quản lý chi nhánh: Quản lý nhân viên, đơn hàng, báo cáo doanh thu
  case WAITER = 'waiter'; // Nhân viên phục vụ: Nhận & phục vụ món, xử lý đơn hàng tại bàn
  case KITCHEN_STAFF = 'kitchen_staff'; // Nhân viên bếp: Chuẩn bị món ăn, cập nhật trạng thái vé bếp
  case CASHIER = 'cashier'; // Thu ngân: Xử lý thanh toán, áp dụng voucher, quản lý hóa đơn
  case DELIVERY_STAFF = 'delivery_staff'; // Nhân viên giao hàng: Giao đơn, cập nhật trạng thái giao hàng
  case INVENTORY_STAFF = 'inventory_staff'; // Nhân viên kho: Quản lý nhập/xuất kho, kiểm tra tồn kho

  /**
   * Lấy mô tả của vai trò
   */
  public function description(): string
  {
    return match ($this) {
      self::ADMIN => 'Quản trị viên: Toàn quyền quản lý hệ thống.',
      self::MANAGER => 'Quản lý chi nhánh: Quản lý nhân viên, đơn hàng, báo cáo doanh thu.',
      self::WAITER => 'Nhân viên phục vụ: Nhận & phục vụ món, xử lý đơn hàng tại bàn.',
      self::KITCHEN_STAFF => 'Nhân viên bếp: Chuẩn bị món ăn, cập nhật trạng thái vé bếp.',
      self::CASHIER => 'Thu ngân: Xử lý thanh toán, áp dụng voucher, quản lý hóa đơn.',
      self::DELIVERY_STAFF => 'Nhân viên giao hàng: Giao đơn, cập nhật trạng thái giao hàng.',
      self::INVENTORY_STAFF => 'Nhân viên kho: Quản lý nhập/xuất kho, kiểm tra tồn kho.',
    };
  }

  /**
   * Kiểm tra giá trị có hợp lệ hay không
   */
  public static function isValid(string $role): bool
  {
    return in_array($role, self::casesAsArray());
  }

  /**
   * Lấy danh sách vai trò dưới dạng mảng
   */
  public static function casesAsArray(): array
  {
    return array_column(self::cases(), 'value');
  }

  /**
   * Lấy một giá trị ngẫu nhiên để fake dữ liệu
   */
  public static function fake(): self
  {
    return Arr::random(self::cases());
  }
}
