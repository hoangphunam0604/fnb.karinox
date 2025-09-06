<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerService extends BaseService
{

  protected function model(): Customer
  {
    return new Customer();
  }

  protected function applySearch($query, array $params)
  {
    if (!empty($params['keyword'])):
      $keyword = $params['keyword'];
      $query->where(function ($subQuery) use ($keyword) {
        $subQuery->where('phone', 'LIKE', '%' . $keyword . '%')
          ->orWhere('fullname', 'LIKE', '%' . $keyword . '%')
          ->orWhere('email', 'LIKE', '%' . $keyword . '%')
          ->orWhere('loyalty_card_number', 'LIKE', '%' . $keyword . '%');
      });
    endif;
    if (!empty($params['membership_level_id']))
      $query->where('membership_level_id', $params['membership_level_id']);

    if (!empty($params['status']))
      $query->where('status', $params['status']);

    $query = parent::applySearch($query, $params);
    return $query;
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


  function receiveNewMemberGift($customerId)
  {
    $customer = Customer::findOrFail($customerId);
    if ($customer->received_new_member_gift)
      abort(403, "Lỗi: Đã nhận quà thành viên mới vào lúc: " . $customer->received_new_member_gift->format('H:i:s d/m/Y'));

    if (!$customer->last_purchase_at)
      abort(403, "Lỗi: Thành viên chưa phát sinh giao dịch, không thể nhận quà");
    $customer->received_new_member_gift = now();
    $customer->save();
    return $customer;
  }
  public function receiveBirthdayGift(int $customerId)
  {
    $customer = Customer::findOrFail($customerId);
    if (!$customer->canReceiveBirthdayGifts())
      abort(403, "Đã nhận quà sinh nhật năm nay vào lúc:" . $customer->last_birthday_gift->format('H:i:s d/m/Y'));
    $customer->last_birthday_gift = now();
    $customer->save();
    return $customer;
  }
}
