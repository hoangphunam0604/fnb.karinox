<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerService
{
  /**
   * Tạo khách hàng mới
   */
  public function createCustomer(array $data)
  {
    $data['membership_level_id']  = 1;
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

  public function saveCustomer(array $data) {}

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
    try {
      return Customer::with('membershipLevel')
        ->where('phone', $keyword)
        ->orWhere('email', $keyword)
        ->orWhere('loyalty_card_number', $keyword)
        ->firstOrFail();
    } catch (ModelNotFoundException $e) {
      abort(404, 'Không tìm thấy khách hàng.');
    }
  }

  /**
   * Lấy danh sách khách hàng (phân trang)
   */
  public function getCustomers($perPage = 10, $keyword = null)
  {
    return Customer::with('membershipLevel')->when($keyword, function ($query) use ($keyword) {
      $query->where(function ($q) use ($keyword) {
        $q->where('phone', 'like', "%$keyword%")
          ->orWhere('email', 'like', "%$keyword%")
          ->orWhere('loyalty_card_number', 'like', "%$keyword%")
          ->orWhere('fullname', 'like', "%$keyword%");
      });
    })
      ->orderBy('created_at', 'desc')->paginate($perPage);
  }

  public function getCustomerMembershipLevel($customerId)
  {
    $customer = Customer::findOrFail($customerId);
    return $customer->membershipLevel;
  }
  /**
   * Lấy cấp độ tiếp theo của khách hàng
   */
  public function getNextLevel(Customer $customer)
  {
    return MembershipLevel::where('min_spent', '>', $customer->membershipLevel->min_spent ?? 0)
      ->orderBy('min_spent')
      ->first();
  }

  /**
   * Cập nhật tổng số tiền đã chi tiêu
   */
  public function updateTotalSpent($customer, $total_amount)
  {
    $customer->total_spent += $total_amount;
    $customer->save();
  }
  /**
   * Lấy số điểm hoặc tổng chi tiêu cần để thăng cấp tiếp theo
   */
  public function getNextMembershipLevel(Customer $customer)
  {
    $nextLevel = $this->getNextLevel($customer);

    if (!$nextLevel) {
      return null;
    }

    return [
      'next_level' => $nextLevel->name,
      'points_needed' => max(0, $nextLevel->min_spent - $customer->total_spent),
    ];
  }

  /**
   * Logic cấp độ thành viên
   */
  public function updateMembershipLevel($customer)
  {
    $points = $customer->loyalty_points;

    // Tìm hạng cao nhất mà khách hàng có thể đạt được
    $newLevel = MembershipLevel::where('min_spent', '<=', $points)
      ->where(function ($query) use ($points) {
        $query->whereNull('max_spent')
          ->orWhere('max_spent', '>=', $points);
      })
      ->orderBy('rank', 'desc')
      ->first();

    if ($newLevel && (!$customer->membership_level_id || $customer->membershipLevel->rank < $newLevel->rank)) {
      // Chỉ cập nhật nếu có thay đổi về hạng
      if ($customer->membership_level_id !== $newLevel->id) {
        $customer->membership_level_id = $newLevel->id;
        $customer->save();
      }
    }
  }

  public function downgradeMembershipLevel($customer) {}
}
