<?php

namespace App\Services;

use App\Models\Customer;
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
    return $customer->membership_level_id;
  }

  public function updatePoint($customerId, $point)
  {

    $customer = Customer::findOrFail($customerId);

    $customer->loyalty_points += $point;
    $customer->reward_points += $point;
    $customer->save();

    // Cập nhật cấp độ thành viên
    $customer->updateMembershipLevel();
  }
}
