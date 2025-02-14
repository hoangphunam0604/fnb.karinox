<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PointHistory;
use Illuminate\Support\Facades\DB;

class PointService
{
  /**
   * Cộng điểm tích lũy hoặc điểm thưởng cho khách hàng
   */
  public function addPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, string $sourceType = null, int $sourceId = null, string $note = null)
  {
    return DB::transaction(function () use ($customer, $loyaltyPoints, $rewardPoints, $sourceType, $sourceId, $note) {
      $previousLoyaltyPoints = $customer->loyalty_points;
      $previousRewardPoints = $customer->reward_points;

      $newLoyaltyPoints = $previousLoyaltyPoints + $loyaltyPoints;
      $newRewardPoints = $previousRewardPoints + $rewardPoints;

      // Cập nhật điểm cho khách hàng
      $customer->update([
        'loyalty_points' => $newLoyaltyPoints,
        'reward_points' => $newRewardPoints
      ]);

      // Lưu vào lịch sử điểm
      return PointHistory::create([
        'customer_id' => $customer->id,
        'transaction_type' => 'earn',
        'previous_loyalty_points' => $previousLoyaltyPoints,
        'previous_reward_points' => $previousRewardPoints,
        'loyalty_points_changed' => $loyaltyPoints,
        'reward_points_changed' => $rewardPoints,
        'loyalty_points_after' => $newLoyaltyPoints,
        'reward_points_after' => $newRewardPoints,
        'source_type' => $sourceType,
        'source_id' => $sourceId,
        'note'  =>  $note
      ]);
    });
  }

  /**
   * Sử dụng điểm thưởng của khách hàng
   */
  public function redeemPoints(Customer $customer, int $rewardPoints, string $usageType = null, int $usageId = null)
  {
    return DB::transaction(function () use ($customer, $rewardPoints, $usageType, $usageId) {
      if ($customer->reward_points < $rewardPoints) {
        throw new \Exception("Khách hàng không đủ điểm thưởng để sử dụng.");
      }

      $previousRewardPoints = $customer->reward_points;
      $newRewardPoints = $previousRewardPoints - $rewardPoints;

      // Cập nhật điểm thưởng
      $customer->update(['reward_points' => $newRewardPoints]);

      // Lưu vào lịch sử điểm
      return PointHistory::create([
        'customer_id' => $customer->id,
        'transaction_type' => 'redeem',
        'previous_loyalty_points' => $customer->loyalty_points,
        'previous_reward_points' => $previousRewardPoints,
        'loyalty_points_changed' => 0,
        'reward_points_changed' => -$rewardPoints,
        'loyalty_points_after' => $customer->loyalty_points,
        'reward_points_after' => $newRewardPoints,
        'usage_type' => $usageType,
        'usage_id' => $usageId,
      ]);
    });
  }

  /**
   * Lấy lịch sử điểm của khách hàng
   */
  public function getCustomerPointHistory(Customer $customer, int $limit = 10)
  {
    return PointHistory::where('customer_id', $customer->id)
      ->orderBy('created_at', 'desc')
      ->limit($limit)
      ->get();
  }
}
