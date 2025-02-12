<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CustomerService
{
  /**
   * Tạo khách hàng mới
   */
  public function createCustomer(array $data)
  {
    return Customer::create($data);
  }

  /**
   * Cập nhật thông tin khách hàng
   */
  public function updateCustomer($customerId, array $data)
  {
    $customer = Customer::findOrFail($customerId);
    $customer->update($data);
    return $customer;
  }

  /**
   * Xóa khách hàng (chỉ khi không có đơn hàng)
   */
  public function deleteCustomer($customerId)
  {
    $customer = Customer::findOrFail($customerId);

    return $customer->delete();
  }

  /**
   * Tìm kiếm khách hàng theo số điện thoại, email hoặc loyalty_card_number
   */
  public function findCustomer($keyword)
  {
    return Customer::where('phone', $keyword)
      ->orWhere('email', $keyword)
      ->orWhere('loyalty_card_number', $keyword)
      ->first();
  }

  /**
   * Lấy danh sách khách hàng (phân trang)
   */
  public function getCustomers($perPage = 10)
  {
    return Customer::orderBy('created_at', 'desc')->paginate($perPage);
  }

  public function getCustomerMembershipLevel($customerId)
  {
    $customer = Customer::findOrFail($customerId);
    return $customer->membershipLevel;
  }

  /**
   * Cộng điểm từ hóa đơn hoàn thành
   */
  public function addPointsFromInvoice($customerId, $invoice)
  {

    $customer = Customer::findOrFail($customerId);

    // Lấy tỷ lệ quy đổi điểm từ database
    $conversionRate = SystemSetting::where('key', 'point_conversion_rate')->value('value');

    // Nếu không tìm thấy, dùng mặc định 25,000 VNĐ = 1 điểm
    $conversionRate = $conversionRate ? floatval($conversionRate) : 25000;

    $pointsEarned = floor($invoice->total_amount / $conversionRate);

    // Cộng điểm tích lũy (loyalty_points)
    $customer->loyalty_points += $pointsEarned;

    // Mặc định không có hệ số nhân
    $multiplier = 1;

    // Kiểm tra nếu là ngày sinh nhật và có hệ số nhân từ membership_levels
    if ($customer->isEligibleForBirthdayBonus() && $customer->membershipLevel) {
      $multiplier = $customer->membershipLevel->reward_multiplier ?? 1;
      $customer->last_birthday_bonus_date = Carbon::now();
    }

    // Cộng điểm thưởng (reward_points) với hệ số nhân
    $customer->reward_points += $pointsEarned * $multiplier;

    // Cập nhật tổng số tiền đã chi tiêu
    $customer->total_spent += $invoice->total_amount;

    // Lưu lại thông tin cập nhật
    $customer->save();

    // Cập nhật cấp độ thành viên
    $customer->updateMembershipLevel();
  }

  public function updateLoyaltyPoints($customerId, $point)
  {
    $customer = Customer::findOrFail($customerId);
    $customer->loyalty_points += $point;
    $customer->save();

    // Cập nhật cấp độ thành viên
    $customer->updateMembershipLevel();
  }

  public function updateRewardPoints($customerId, $point)
  {
    $customer = Customer::findOrFail($customerId);
    $customer->reward_points += $point;
    $customer->save();
  }
}
